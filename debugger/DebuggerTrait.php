<?php

use updash\types\DebugMessage;

/**
 * Debugger
 */
trait DebuggerTrait
{
    /**
     * Enable or disable the internal debug log. If enabled, messages will be stored and can be retrieved with debugLog().
     *
     * @param bool $setting
     * @return bool|null
     */
    public function debugLogEnabled($setting = null)
    {
        static $enabled = null;

        if ($setting !== null) {
            $enabled = (bool)$setting;
        }

        return $enabled;
    }

    /**
     * Generate a DebugMessage, which can be consumed in a variety of ways
     * - if debugLogEnabled() is true, it will be added to an internal log
     * - if running in CLI, it will be printed to the console
     * - if a Redis connection is available, it will be published to the 'updash:debug' channel
     *
     * @param string|array $text
     * @return void
     */
    protected function debug($text)
    {
        // Get the backtrace and find the last frame
        $backtrace     = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $lastFrame     = array_shift($backtrace);
        $previousFrane = array_shift($backtrace);

        // Extract class, file, and line information
        $class  = get_class($this);
        $method = !empty($previousFrane['function']) ? $previousFrane['function'] : null;
        $file   = str_replace(path_to_root(), '', $lastFrame['file']);
        $line   = $lastFrame['line'];

        // Create and populate the DebugMessage object
        $debugMessage = new DebugMessage();
        $debugMessage->setClass($class);
        $debugMessage->setFile($file);
        $debugMessage->setFunction($method);
        $debugMessage->setLine($line);
        $debugMessage->setMessage($text);

        // Add to the debuglog
        if ($this->debugLogEnabled()) {
            $this->debugLog($text);
        }

        // Print to console if running in CLI
        if (\updash\traits\isCommandLineInterface()) {
            $debugMessage->printLine();
        }

        // Broadcast via Redis if available
        if ($redis = \updash\traits\debugBroadcaster()) {
            try {
                $redis->publish('updash:debug', $debugMessage);
            } catch (Exception $e) {
                // Fail silently
            }
        }
    }

    /**
     * Internal debug log storage and retrieval. Off by default, can be enabled with debugLogEnabled(true).
     *
     * @param string $message
     * @return array|void
     */
    protected function debugLog($message = null)
    {
        static $log = [];
        static $startTime;

        if ($message === null) {
            return $log;
        }

        if ($startTime === null) {
            $startTime = microtime(true);
        }

        $log[] = [$message, microtime(true) - $startTime];
    }
}
