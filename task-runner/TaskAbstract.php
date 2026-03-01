<?php

use updash\exceptions\DateTimeException;
use updash\exceptions\DirectoryNotCreatedException;
use updash\exceptions\FileNotWritableException;
use updash\services\Profiler;
use updash\traits\Debugger;
use updash\traits\Multithreading;
use updash\traits\UsesModels;

/**
 * Parent class for all tasks.
 */
abstract class TaskAbstract
{
    use UsesModels;
    use Debugger;
    use Multithreading;

    /**
     * @var bool Run even if it is disabled
     */
    protected $configAllowRunningWhileDisabled = false;

    /**
     * @var bool Run the task perpetually in a loop
     */
    protected $configLoop = false;

    /**
     * @var int Sleep time in seconds between loops
     */
    protected $configLoopSleep = 10;

    /**
     * @var bool Automatically exit occassionally, to be restarted by cron
     */
    protected $configNapsEnabled = true;

    /**
     * @var int How often th
     */
    protected $configNapsFrequency = 3600;

    /**
     * @var bool Whether to reconnect to the database every loop
     */
    protected $configReconnectDBEveryLoop = false;

    /**
     * @var int Counter
     */
    protected $counter = 0;

    /**
     * @var string Current operation
     */
    protected $current;

    /**
     * @var int Total number of items to process
     */
    protected $total = 0;

    /**
     * @var string
     */
    private $log;

    /** @var SplQueue<int> */
    private $rateTimestamps;

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var int
     */
    private $timeLoopStarted;

    /**
     * @var int
     */
    private $timeTaskStarted;

    /**
     *
     */
    public function __construct()
    {
        // Don't capture queries or errors in the profiler
        Profiler::shouldCaptureQueries(false);
        Profiler::shouldCaptureErrors(false);
        Profiler::shouldRecordRequest(false);

        ini_set('display_errors', true);

        $this->rateTimestamps = new SplQueue();

        $this->redis = new Redis();
        $this->redis->connect(\updash\types\Task\REDIS_HOST, \updash\types\Task\REDIS_PORT);
    }

    /**
     * @return void
     */
    public function allowRunningEvenIfDisabled()
    {
        $this->configAllowRunningWhileDisabled = true;
    }

    /**
     * Returns true if the task is scheduled to run now
     *
     * @return bool
     */
    public function isTaskScheduledToRun()
    {
        // If there is no next run time, allow it to run
        if (!$nextRun = $this->getTaskNextRunTime()) {
            return true;
        }

        // If the next run time is in the future, do not run
        if (time() < $nextRun) {
            return false;
        }

        return true;
    }

    /**
     * Run this task
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->logInitialize();
        } catch (Exception $e) {
            $this->debug('Error initializing log: ' . $e->getMessage());
        }

        if (!\updash\types\Task\isCommandLineInterface()) {
            throw new RuntimeException('This must be run from the command line');
        }

        if ($this->configLoop) {
            $this->runInLoop();
        } else {
            $this->runOnce();
        }
    }

    /**
     * @return void
     */
    public function runInLoop()
    {
        $this->startupTask();

        while (true) {
            $this->startupLoop();

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            $this->main();

            $this->resetCounter();
            $this->resetTotal();

            $this->teardownLoop();
        }

        /** @noinspection PhpUnreachableStatementInspection */
        $this->teardownTask();
    }

    /**
     * @return void
     */
    public function runOnce()
    {
        $this->startupTask();

        $this->startupLoop();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $this->main();

        $this->teardownTask();
    }

    /**
     * @param string $message
     * @return null
     */
    protected function debugWithStats($message = '')
    {
        $stats = [
            $this->counter . '/' . $this->total,
            $this->statPercentComplete() . '%',
            $this->statRate() . '/s',
            \updash\types\Task\formatBytesToHuman($this->statMemory()),
            $this->statTimeLoopElapsedFormatted(),
            $this->statTimeRemainingFormatted(),
        ];

        $message = '[' . implode(', ', $stats) . '] ' . $message;

        return $this->debug($message);
    }

    /**
     * Returns the task name
     *
     * @return string
     */
    protected function getTaskName()
    {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * Get the next run time
     *
     * @return int|false
     */
    protected function getTaskNextRunTime()
    {
        return \updash\types\Task\redis()->hGet($this->keyTask(), 'NextRun');
    }

    /**
     * Increment the counter
     *
     * @return void
     */
    protected function incrementCounter()
    {
        $this->counter++;
        $this->rateTimestamps->enqueue(time());
    }

    /**
     * Increment the counter and update at the same time
     *
     * @param array $data
     * @return void
     */
    protected function incrementCounterAndUpdate($data = [])
    {
        $this->incrementCounter();
        $this->update($data);
    }

    /**
     * Write a message to the log file
     *
     * @param array|string $message
     * @return void
     */
    protected function log($message)
    {
        if (is_array($message)) {
            $message = json_encode($message);
        }

        file_put_contents($this->log, $message . "\n", FILE_APPEND);
    }

    /**
     * Refresh the database connection
     *
     * @return void
     */
    protected function refreshDatabaseConnection()
    {
        $this->db->close();
        $this->db->initialize();
    }

    /**
     * @return void
     */
    protected function resetCounter()
    {
        $this->counter = 0;
        $this->resetRateWindow();
    }

    /**
     * Reset the current operation
     *
     * @return void
     */
    protected function resetCurrentOperation()
    {
        // Set the value
        $this->current = '';

        // Store in Redis immediately
        \updash\types\Task\redis()->hSet($this->keyTask(), 'Status', '');
    }

    /**
     * @return void
     */
    protected function resetTotal()
    {
        $this->total = 0;
    }

    /**
     * Set the current operation
     *
     * @param string $operation
     * @return void
     */
    protected function setCurrentOperation($operation)
    {
        // Set the value
        $this->current = $operation;

        // Store in Redis immediately
        \updash\types\Task\redis()->hSet($this->keyTask(), 'Status', $operation);

        // Debug
        $this->debug('Currently: ' . $operation);
    }

    /**
     * Set the next run time
     *
     * @param int|string $schedule
     * @return void
     * @throws DateTimeException
     */
    protected function setNextRun($schedule)
    {
        $DateTime = \updash\types\Task\datetimeParse($schedule);

        $this->update([
            'NextRun' => $DateTime->getTimestamp(),
        ]);
    }

    /**
     * Set the total number of items to process
     *
     * @param int $total
     * @return void
     */
    protected function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return void
     */
    protected function sleepForObservers()
    {
        sleep(1);
    }

    /**
     * @return void
     */
    protected function stopIfBrakeEngaged()
    {
        $brake = \updash\types\Task\redis()->hGet($this->keyTask(), 'Brake') == 1;

        if ($brake == 1) {
            $this->debug('Brake engaged');

            // Update the task state to Halted
            \updash\types\Task\redis()->hSet($this->keyTask(), 'State', 'Halted');
            \updash\types\Task\redis()->hSet($this->keyTask(), 'Brake', 0);

            exit;
        }
    }

    /**
     * Stop if it's naptime
     *
     * @return void
     */
    protected function stopIfNaptime()
    {
        if ($this->configNapsEnabled && (time() - $this->timeTaskStarted) > $this->configNapsFrequency) {
            $this->debug('Taking a nap');

            // Update the task state to Halted
            \updash\types\Task\redis()->hSet($this->keyTask(), 'State', 'Napping');

            exit;
        }
    }

    /**
     * Update the task's data in the database
     * $data can be an array of key-value pairs to update other columns in the task table
     *
     * @param array $data
     * @return void
     */
    protected function update($data = [])
    {
        $stats = [
            'Pid'       => getmypid(),
            'Status'    => $this->current,
            'Counter'   => $this->counter,
            'Total'     => $this->total,
            'Rate'      => $this->statRate(),
            'Memory'    => $this->statMemory(),
            'Files'     => $this->statFilesOpened(),
            'Estimated' => $this->statTimeRemaining(),
        ];

        // Merge the stats with any other data passed in
        $data = array_merge($stats, $data);

        // Add the task to the set of tasks (if it is absent)
        $this->redis->sAdd($this->keyTasks(), $this->getTaskName());

        // Change the values to strings for Redis
        $data = array_map('strval', $data);

        // Store the task data in a hash
        $this->redis->hMset($this->keyTask(), $data);

        // Stop if Brake is engaged
        $this->stopIfBrakeEngaged();

        // Stop if it's naptime
        $this->stopIfNaptime();
    }

    /**
     * Update the task's data in the database, periodically.
     * This will update less frequently than update(), to reduce overhead.
     *
     * @return void
     */
    protected function updatePeriodically()
    {
        if ($this->total < 100) {
            $this->update();

            return;
        }

        if ($this->counter % 100 === 0) {
            $this->update();
        }
    }

    /**
     * Returns true if the task is already running under another process ID
     *
     * @return false|numeric-string
     */
    private function isTaskAlreadyRunning()
    {
        $my_pid = getmypid();
        $task   = $this->getTaskName();

        $pids = explode("\n", shell_exec('ps aux | grep "tasks run ' . $task . '$" | grep -v grep | awk \'{print $2}\''));

        if (count($pids) > 1) {
            foreach ($pids as $pid) {
                if (is_numeric($pid) && $pid != $my_pid) {
                    return $pid;
                }
            }
        }

        return false;
    }

    /**
     * Returns true if the task is enabled
     *
     * @return bool
     */
    private function isTaskEnabled()
    {
        return \updash\types\Task\redis()->hGet($this->keyTask(), 'Enabled') == 1;
    }

    /**
     * Returns true if the task is installed
     *
     * @return bool
     */
    private function isTaskInstalled()
    {
        return \updash\types\Task\redis()->sismember($this->keyTasks(), $this->getTaskName());
    }

    /**
     * Returns the Redis key for this task
     *
     * @return string
     */
    private function keyTask()
    {
        return 'tasks:' . $this->getTaskName();
    }

    /**
     * Returns the Redis key for the set of all tasks
     *
     * @return string
     */
    private function keyTasks()
    {
        return 'tasks';
    }

    /**
     * Initialize the log file for this task
     *
     * @return void
     * @throws DirectoryNotCreatedException
     * @throws FileNotWritableException
     */
    private function logInitialize()
    {
        $this->log = \updash\types\Task\path_to_logs('tasks/' . $this->getTaskName() . '.log');

        // Ensure the log directory is writable
        $logDirectory = dirname($this->log);

        // Create the directory if necessary
        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0777, true) && !is_dir($logDirectory)) {
            throw new DirectoryNotCreatedException(sprintf('Directory "%s" does not exist and could not be created', $logDirectory));
        }

        // Create the file if it doesn't exist
        if (!file_exists($this->log)) {
            file_put_contents($this->log, '');
            chmod($this->log, 0666);
        }

        // Ensure the file is writable
        if (!is_writable($this->log)) {
            throw new FileNotWritableException('Log file is not writable: ' . $this->log);
        }
    }

    /**
     * @return void
     */
    private function resetRateWindow()
    {
        $this->rateTimestamps = new SplQueue();
    }

    /**
     * Executed at the start of each loop
     *
     * @return void
     */
    private function startupLoop()
    {
        if ($this->configReconnectDBEveryLoop) {
            $this->refreshDatabaseConnection();
        }

        // Reset counter
        $this->resetCounter();

        // Reset total
        $this->resetTotal();

        // Clear current text
        $this->resetCurrentOperation();

        // Set the time loop started
        $this->timeLoopStarted = time();

        // Update
        $this->update([
            'State' => 'Working',
        ]);
    }

    /**
     * Executed at the start of the task
     *
     * @return void
     */
    private function startupTask()
    {
        // Exit if the task is not installed
        if (!$this->isTaskInstalled()) {
            $this->debug('Task is not installed. To install, run:');
            $this->debug('php index.php tasks install ' . $this->getTaskName());

            exit;
        }

        // Exit if the task is disabled
        if (!$this->isTaskEnabled() && !$this->configAllowRunningWhileDisabled) {
            $this->debug('Task is not enabled.');
            $this->debug('To run anyway:  php83 index.php tasks force  ' . $this->getTaskName());
            $this->debug('To enable task: php83 index.php tasks enable ' . $this->getTaskName());

            exit;
        }

        // Exit if the task is already running
        if ($pid = $this->isTaskAlreadyRunning()) {
            $this->debug('Task is already running with PID: ' . $pid);

            exit;
        }

        // Exit if the task is not scheduled to run yet
        if (!$this->isTaskScheduledToRun()) {
            $this->debug('Task is not scheduled for another ' . \updash\types\Task\timeSecondsToHms($this->getTaskNextRunTime() - time()));

            exit;
        }

        // Set the time task started
        $this->timeTaskStarted = time();

        // Reset current operation
        $this->resetCurrentOperation();

        // Update
        $this->update([
            'State'    => 'Starting',
            'Started'  => date('Y-m-d H:i:s'),
            'Finished' => null,
        ]);

        // Sleep for observers
        $this->sleepForObservers();
    }

    /**
     * Get the number of files opened by this process
     *
     * @return string
     */
    private function statFilesOpened()
    {
        $result = shell_exec('ls /proc/' . getmypid() . '/fd | wc -l');
        $result = trim($result);

        return $result;
    }

    /**
     * Get memory usage in bytes
     *
     * @return int
     */
    private function statMemory()
    {
        return memory_get_usage(true);
    }

    /**
     * Get the percentage of the task that is complete (counter/total)
     *
     * @return float
     */
    private function statPercentComplete()
    {
        if ($this->total > 0) {
            return round($this->counter / $this->total * 100);
        }

        return 0.0;
    }

    /**
     * Get the rate of the task in items per second
     *
     * @return float|string
     */
    private function statRate()
    {
        $now    = time();
        $cutoff = $now - 60;

        // Drop timestamps older than 60s from the front of the queue
        while (!$this->rateTimestamps->isEmpty()) {
            // SplQueue inherits bottom() from SplDoublyLinkedList (front of queue)
            $oldest = $this->rateTimestamps->bottom();

            if ($oldest >= $cutoff) {
                break;
            }

            $this->rateTimestamps->dequeue();
        }

        $count = $this->rateTimestamps->count();

        if ($count === 0) {
            return 0.0;
        }

        // Use the actual span covered by the window (up to 60s)
        $oldest = $this->rateTimestamps->bottom();
        $span   = max(1, min(60, $now - $oldest + 1));

        return number_format($count / $span, 1, '.', '');
    }

    /**
     * Get the time elapsed since the task started
     *
     * @return int
     */
    private function statTimeLoopElapsed()
    {
        return time() - $this->timeLoopStarted;
    }

    /**
     * Get the time elapsed since the task started formatted as HH:MM:SS
     *
     * @return string
     */
    private function statTimeLoopElapsedFormatted()
    {
        return \updash\types\Task\timeSecondsToHms($this->statTimeLoopElapsed());
    }

    /**
     * @return float|int
     */
    private function statTimeRemaining()
    {
        if ($this->total > 0 && $this->statRate() > 0) {
            return round(($this->total - $this->counter) / $this->statRate());
        }

        return 0;
    }

    /**
     * @return string
     */
    private function statTimeRemainingFormatted()
    {
        return \updash\types\Task\timeSecondsToHms($this->statTimeRemaining());
    }

    /**
     * Executed at the end of each loop
     *
     * @return void
     */
    private function teardownLoop()
    {
        // Sleep for observers
        $this->sleepForObservers();

        // Reset counter
        $this->resetCounter();

        // Reset total
        $this->resetTotal();

        // Clear current text
        $this->resetCurrentOperation();

        // Determine wake time
        $wakeTime = time() + $this->configLoopSleep;

        // Sleep until wake time
        while (time() < $wakeTime) {
            $this->update([
                'State' => 'Idle',
            ]);

            sleep(1);
        }
    }

    /**
     * Executed at the end of the task
     *
     * @return void
     */
    private function teardownTask()
    {
        // Announce
        $this->debug('Finished');

        // Reset the counter
        $this->resetCounter();

        // Reset the total
        $this->resetTotal();

        // Update
        $this->update([
            'Finished' => date('Y-m-d H:i:s'),
            'Status'   => '',
            'State'    => '',
        ]);
    }
}
