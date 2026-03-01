<?php

use updash\services\FlagManager;
use updash\types\Task\TaskAbstract;

/**
 *
 */
class flagger extends TaskAbstract
{
    /**
     * @var FlagManager
     */
    private $flagManager;

    /**
     * @return void
     */
    protected function main()
    {
        $this->flagManager = new FlagManager();

        $this->checkDmsFeeds();
    }

    /**
     * Check all DMS feeds and flag those that are inactive or have not been updated in over 14 days.
     *
     * @return void
     */
    protected function checkDmsFeeds()
    {
        // Get all feeds
        $feeds = $this->feed_model->getFeeds();

        foreach ($feeds as $feed) {
            // Define flag
            $flag = 'feed_' . $feed['Id'];

            // Determine the latest sales or service date
            $latestDate          = max(strtotime($feed['SalesEnd']), strtotime($feed['ServiceEnd']));
            $latestDateFormatted = \updash\tasks\dateAmerican($latestDate);

            // Flag the feed if it is active and the latest date is more than 14 days old
            if ($feed['Active'] == 1 && $latestDate < time() - 60 * 60 * 24 * 14) {
                $this->debug('Flagging feed: ' . $feed['Fcid'] . ' LU: ' . $feed['SalesEnd'] . ' LI: ' . $feed['ServiceEnd'] . ' LM ' . date('Y-m-d H:i:s', $latestDate));
                $this->flagManager->raiseFlag($flag, 'Not updated since ' . $latestDateFormatted);
            } else {
                $this->flagManager->lowerFlag($flag);
            }
        }
    }
}
