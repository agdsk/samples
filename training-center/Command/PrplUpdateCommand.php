<?php

namespace AppBundle\Command;

use AppBundle\Library\DirectoryService;
use AppBundle\Model\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Illuminate\Database\Capsule\Manager as Capsule;

class PrplUpdateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('prpl:update')->setDescription('Update users from LDAP');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Fetching list of OPIDs</info>');

        $opids = Capsule::table('users')->select('opid')->where('opid', '<>', '')->orderBy('opid')->pluck('opid');

        $directoryService = new DirectoryService($this->getContainer()->getParameter('ldap_url'));
        $directoryService->connect();
        $directoryService->bindAnonymously();

        $total = count($opids);
        $i     = 0;

        $output->writeln('<info>' . $total . ' OPIDs found</info>');

        foreach ($opids as $opid) {
            $i++;

            $User = User::where('opid', $opid)->first();

            if (!$User) {
                $output->writeln('<error>- Failed to retriever user with opid ' . $opid . '</error>');

                continue;
            }

            $output->writeln('<comment>' . $i . '/' . $total . ' ' . $opid . ' Retrieved ' . $User->first_name . ' ' . $User->last_name . '</comment>');

            try {
                if (!$user = $directoryService->searchForUser($opid)) {
                    $output->writeln('<info>- No results for opid ' . $opid . ' in directory. Leaving database record intact, continuing</info>');

                    continue;
                }

                $user = $directoryService->tidyUser($user);

                $output->writeln('<comment>- Updating ' . $i . '/' . $total . ' ' . $user['opid'] . ' ' . $user['first_name'] . ' ' . $user['last_name'] . '</comment>');

                $User->first_name = $user['first_name'];
                $User->last_name  = $user['last_name'];
                $User->title      = $user['title'];
                $User->department = $user['department'];
                $User->setAttribute('email', $user['email']);

                $User->Save();

                $output->writeln('<comment>- Success</comment>');
            } catch (\PDOException $e) {
                if (strstr($e->getMessage(), 'users_email_unique')) {
                    $output->writeln('<error>- Could not update: Email address is already in use</error>');

                    $User2 = User::where('email', $user['email'])->first();

                    $output->writeln('<info>- Offending record is user ' . $User2->id . ' ' . $User2->opid . ' ' . $User2->first_name . ' ' . $User2->last_name . ' ' . $User2->email . '</info>');

                    $new_email_address = $User2->opid . '@flhosp.net';

                    $User2->setAttribute('email', $new_email_address);
                    $User2->Save();

                    $output->writeln('<info>- Changing user ' . $User2->id . '\'s email address to  ' . $new_email_address . '</info>');

                    $output->writeln('<comment>- Updating ' . $i . '/' . $total . ' ' . $user['opid'] . ' ' . $user['first_name'] . ' ' . $user['last_name'] . ' again</comment>');

                    $User->first_name = $user['first_name'];
                    $User->last_name  = $user['last_name'];
                    $User->title      = $user['title'];
                    $User->department = $user['department'];
                    $User->setAttribute('email', $user['email']);

                    $output->writeln('<info>- Success</info>');

                    $User->Save();
                } else {
                    $output->writeln('<error> Uncaught Exception: ' . $e->getMessage() . '</error>');
                    continue;
                }
            }
        }
    }
}
