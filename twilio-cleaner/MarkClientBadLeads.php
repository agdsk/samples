<?php

declare(strict_types=1);

use App\Traits\Multithreading;
use App\Traits\PhoneValidation;
use App\Updash;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Finds leads that have bad phone numbers and writes them to a log file.
 */
#[AsCommand(
    name: 'badleads:client',
    description: 'Marks bad leads for a single client',
)]
class MarkClientBadLeads extends Command
{
    use Multithreading;
    use PhoneValidation;

    private int $maxRate = 300;

    private int $maxChildren = 100;

    private Updash $updash;

    private array $results = [
        'badLeads'          => 0,
        'phoneNumbersTotal' => 0,
        'percent'           => 0,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->updash = new Updash();

        // Set the inherited maximum children to the locally defined config
        $this->setMaxChildren($this->maxChildren);
    }

    /**
     * @param SymfonyStyle $io
     * @param int          $clientId
     * @param bool         $dryRun
     * @return int
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Client ID')]
        int $clientId,
        #[Option(description: 'Run without marking leads as bad')]
        bool $dryRun = false
    ): int {
        // Get the Twilio client
        $twilio = $this->updash->getTwilioClient($clientId);

        $this->updash->reconnect();

        $clientName = $this->updash->getClientName($clientId);

        // Verify Twilio authentication
        try {
            $account = $twilio->api->v2010->accounts($twilio->getAccountSid())->fetch();
            $io->info(sprintf('Twilio authenticated successfully. Account: %s (Status: %s)', $account->friendlyName, $account->status));
        } catch (Throwable $e) {
            $io->error(sprintf('Twilio authentication failed for ClientId %s (%s): %s', $clientId, $clientName, $e->getMessage()));
            $this->updash->updateClientLastCheckedForBadLeads($clientId);

            return Command::FAILURE;
        }

        if (!$clientName) {
            $io->info(sprintf('Client ID %d has no name, skipping', $clientId));

            return Command::SUCCESS;
        }

        $io->section(sprintf('Scanning Client ID: %d (%s)', $clientId, $clientName));

        // Get all phone numbers for the client
        $clientLeadPhoneNumbers = $this->updash->getClientLeadPhoneNumbers($clientId);
        $phoneNumbers           = $clientLeadPhoneNumbers->fetchAll();
        $phoneNumbersTotal      = $clientLeadPhoneNumbers->rowCount();

        $this->results['phoneNumbersTotal'] = $phoneNumbersTotal;

        // If there are no phone numbers, exit early
        if ($phoneNumbersTotal < 1) {
            $io->warning('No leads found for client ID ' . $clientId);
            $this->updash->updateClientLastCheckedForBadLeads($clientId);

            return Command::SUCCESS;
        }

        // Initialize the progress bar
        $progressBar = $this->customProgressBar($io, $phoneNumbersTotal);
        $progressBar->setFormat(' %bar% %current%/%max% %percent:3s%% %rate%/s | Bad: %bad_leads% | Memory: %memory:6s% | Elapsed: %elapsed:6s% | Remaining: %remaining:-6s%');
        $progressBar->setMessage('0', 'bad_leads');
        $progressBar->setMessage('0.0', 'rate');
        $progressBar->start();

        // Initialize counters
        $counter     = 0;
        $badLeads    = 0;
        $timeStarted = microtime(true);

        // Get the shared memory object
        $sharedMemory = $this->sharedMemory();

        // Initialize the bad lead IDs array
        /** @noinspection PhpUndefinedFieldInspection */
        $sharedMemory->badLeadIds = [];

        // Foreach phone number
        foreach ($phoneNumbers as $phone) {
            if ($this->stopProcessing) {
                throw new RuntimeException('Child process exited with non-zero code');
            }

            // Measure progress
            $timeElapsed = microtime(true) - $timeStarted;
            $rate        = $counter / ($timeElapsed ?: 1);

            // If the rate is too high, wait until it drops below the target rate
            while ($rate > $this->maxRate) {
                $timeElapsed = microtime(true) - $timeStarted;
                $rate        = $counter / ($timeElapsed ?: 1);
            }

            // Isolate the phone number and lead ID
            $leadId = $phone['LeadId'];

            // Increment the processed phone counter
            $counter++;

            // Count the number of bad leads
            $badLeads = count($sharedMemory->badLeadIds);

            // Advance the progress bar
            $progressBar->setMessage(sprintf('%-7.2f', $rate), 'rate');
            $progressBar->setMessage((string)$badLeads, 'bad_leads');
            $progressBar->advance();

            // Wait for a spare child process to become available
            $this->waitForSpareChild();

            // Fork a child process
            $this->forkProcess();

            // Everything inside this block is only executed by the newly forked child process
            if ($this->isChildProcess()) {
                // If the phone number is invalid, mark the lead as bad and terminate the child process
                if (!$formattedPhone = $this->formatPhoneNumberE164AndValidate($phone['Phone'])) {
                    $sharedMemory->badLeadIds = array_merge($sharedMemory->badLeadIds, [$leadId]);
                    $this->exitChild();
                }

                // Get the last 5 messages sent to the phone number
                try {
                    $messages = $twilio->messages->read(['to' => $formattedPhone], 5);// If there aren't exactly 5 messages, do nothing. Kill the child.
                } catch (Throwable $e) {
                    $io->writeln('Child ' . getmypid() . ' error ' . $e->getMessage());

                    $this->exitChild(1);
                }

                if (count($messages) !== 5) {
                    $this->exitChild();
                }

                // Assume all messages are failed
                $allFailed = true;

                // If any of the messages are not failed, clear the assumption
                foreach ($messages as $message) {
                    if ($message->status !== 'failed' && $message->status !== 'undelivered') {
                        $allFailed = false;
                        break;
                    }
                }

                // If all messages are failed, mark the lead as bad
                if ($allFailed) {
                    $sharedMemory->badLeadIds = array_merge($sharedMemory->badLeadIds, [$leadId]);
                }

                $this->exitChild();
            }
        }

        // Wait for all child processes to finish before proceeding
        $this->waitForAllChildren();

        // Finish the progress bar
        $progressBar->finish();

        // Calculate the total elapsed time
        $totalElapsed = (int)(microtime(true) - $timeStarted);
        $hours        = floor($totalElapsed / 3600);
        $minutes      = floor(($totalElapsed % 3600) / 60);
        $seconds      = $totalElapsed % 60;
        $percent      = ($badLeads / $phoneNumbersTotal) * 100;

        // Capture the results
        $this->results['badLeads'] = $badLeads;
        $this->results['percent']  = $percent;

        $io->newLine(2);
        $io->text(sprintf('%d/%d leads are bad (%.2f%%), finished in %02d:%02d:%02d', $badLeads, $phoneNumbersTotal, $percent, $hours, $minutes, $seconds));

        // If dry run mode is enabled, exit early
        if ($dryRun) {
            $io->note('DRY RUN MODE: No leads will be marked as bad');

            return Command::SUCCESS;
        }

        $this->updash->updateClientLastCheckedForBadLeads($clientId);

        // If there are no bad leads, exit early
        if ($badLeads < 1) {
            return Command::SUCCESS;
        }

        // Prompt the user to confirm the action
        if (!$io->confirm(sprintf('Mark %d leads as bad?', $badLeads))) {
            return Command::SUCCESS;
        }

        $io->info(sprintf('Marking %d leads as bad...', $badLeads));

        // Initialize the progress bar
        $progressBar = $this->customProgressBar($io, $phoneNumbersTotal);
        $progressBar->setFormat(' %bar% %current%/%max% %percent:3s%% | Memory: %memory:6s%');
        $progressBar->start();

        foreach ($sharedMemory->badLeadIds as $badLeadId) {
            //$io->writeln(sprintf('Marking lead %d as bad...', $badLeadId));
            $this->updash->markLeadAsBad($badLeadId, $clientId);
            $progressBar->advance();
        }

        return Command::SUCCESS;
    }

    /**
     * @param SymfonyStyle $io
     * @param int          $phoneNumbersTotal
     * @return ProgressBar
     */
    private function customProgressBar(SymfonyStyle $io, int $phoneNumbersTotal): ProgressBar
    {
        $progressBar = new ProgressBar($io, $phoneNumbersTotal);
        $progressBar->setBarCharacter('<fg=green;bg=red>█</>');
        $progressBar->setEmptyBarCharacter('<fg=red;bg=red>█</>');
        $progressBar->setProgressCharacter('<fg=green;bg=red>█</>');
        $progressBar->minSecondsBetweenRedraws(0.1);

        return $progressBar;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }
}
