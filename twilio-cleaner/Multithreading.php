<?php

declare(strict_types=1);

namespace App\Traits;

use Fuz\Component\SharedMemory\SharedMemory;
use Fuz\Component\SharedMemory\Storage\StorageFile;
use JetBrains\PhpStorm\NoReturn;

use function count;

/**
 * Trait for multithreading via forking processes
 */
trait Multithreading
{
    /**
     * @var array
     */
    protected array $children = [];

    /**
     * @var int
     */
    protected int $childrenMax = 5;

    /**
     * @var bool True if this is a child process, null or false otherwise
     */
    protected bool $isChildProcess = false;

    /**
     * @var bool True if this is a child failed and processing should stop
     */
    protected bool $stopProcessing = false;

    /**
     * @return int|null
     */
    protected function forkProcess(): ?int
    {
        // Fork the process
        $pid = pcntl_fork();

        // Handle fork failure
        if ($pid === -1) {
            die('Could not fork process');
        }

        // If this is the parent process, track the child
        if ($pid) {
            $this->children[$pid] = true;
        } else {
            $this->isChildProcess = true;
        }

        return $pid;
    }

    /**
     * @return bool
     */
    protected function isChildProcess(): bool
    {
        return $this->isChildProcess === true;
    }

    /**
     * @param int $code
     * @return void
     */
    #[NoReturn]
    protected function exitChild(int $code = 0): void
    {
        exit($code);
    }

    /**
     * Terminate the child process
     *
     * @return void
     */
    protected function killChild(): void
    {
        // Terminate the child process
        posix_kill(posix_getpid(), SIGKILL);
    }

    /**
     * Wait for all child processes to finish
     *
     * @return void
     */
    protected function waitForAllChildren(): void
    {
        while (count($this->children) > 0) {
            $this->reapOneChild();
        }
    }

    /**
     * @return void
     */
    protected function reapOneChild(): void
    {
        $pid = pcntl_wait($status);

        if ($pid > 0) {
            unset($this->children[$pid]);

            // Child exited normally
            if (pcntl_wifexited($status)) {
                $exitCode = pcntl_wexitstatus($status);

                // Treat any non-zero as failure
                if ($exitCode !== 0) {
                    $this->stopProcessing = true;
                }
            }

            // Child was killed by signal
            if (pcntl_wifsignaled($status)) {
                $this->stopProcessing = true;
            }
        }
    }

    /**
     * Wait for child processes to finish if at max
     *
     * @return void
     */
    protected function waitForSpareChild(): void
    {
        while (count($this->children) >= $this->childrenMax) {
            $this->reapOneChild();
        }
    }

    /**
     * @return SharedMemory
     */
    protected function sharedMemory(): SharedMemory
    {
        static $storage;
        static $shared;

        if (!$storage) {
            $storage = new StorageFile('/tmp/' . uniqid('', true) . '.sync');
        }

        if (!$shared) {
            $shared = new SharedMemory($storage);
        }

        return $shared;
    }

    /**
     * @param int $max
     * @return void
     */
    protected function setMaxChildren(int $max): void
    {
        $this->childrenMax = $max;
    }
}
