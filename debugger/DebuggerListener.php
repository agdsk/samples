<?php

use application\types\Command\CommandAbstract;
use application\types\DebugMessage;

/**
 * Start all tasks that are enabled
 */
class DebuggerListener extends CommandAbstract
{
    /**
     * Linux users to Application user names map
     */
    const USER_MAP = [
        'aaron'  => 'Aaron Gidusko',
    ];

    /**
     * @return void
     */
    public function execute()
    {
        $redis   = \application\commands\debugBroadcaster();
        $channel = 'application:debug';

        // For Pub/Sub we usually want a blocking read, so disable read timeout:
        $redis->setOption(Redis::OPT_READ_TIMEOUT, -1);

        // Simple message handler
        $callback = function (Redis $client, $chan, $message) {
            /** @var DebugMessage $message */
            $message = unserialize($message);

            // Save the username statically so we don't call debugLinuxUser() on every message
            static $userName = null;

            // First time, get the username
            if ($userName === null) {
                $userName = \application\commands\debugLinuxUser();
            }

            // If the message has a username filter, and it doesn't match the current user, skip it
            if (array_key_exists($userName, self::USER_MAP) && $message->getContext()->getUserName() !== self::USER_MAP[$userName]) {
                return;
            }

            $message->printLine();
        };

        // Subscribe (exact channel).
        if (!$redis->subscribe([$channel], $callback)) {
            throw new RuntimeException('Failed to subscribe to Redis channel: ' . $channel);
        }
    }
}
