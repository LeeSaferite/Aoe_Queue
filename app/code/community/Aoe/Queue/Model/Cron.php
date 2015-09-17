<?php
/**
 * AOE Queue - Queue implementation for Magento based on Zend Queue
 *
 * Cron model handles the processeing of queued jobs
 * 
 * @category Mage
 * @package  Aoe_Queue
 * @author   Fabrizio Branca
 * @since    2012-11-09
 */
class Aoe_Queue_Model_Cron
{
    /**
     * Processes the queue, executing as necessary
     * @return array Statistics
     */
    public function processQueue()
    {
        $starttime = microtime(true);
        $maxRuntime = 60; // TODO: read from configuration

        $queueNames = Mage::getSingleton('aoe_queue/queue')->getQueues();

        /* @var $queues Aoe_Queue_Model_Queue[] */
        $queues = array();
        foreach ($queueNames as $queueName) {
            $tmp = Mage::getModel('aoe_queue/queue', $queueName); /* @var $tmp Aoe_Queue_Model_Queue */
            if ($tmp->count() == 0) {
                // $statistics[$queueName] = 'deleted';
                // $tmp->deleteQueue();
            } else {
                $queues[$queueName] = $tmp;
            }
        }

        // process
        $statistics = array();
        // while there are queues with messages left
        while (((microtime(true) - $starttime) < $maxRuntime) && count($queues)) {
            foreach ($queues as $queueName => $queue) {
                $messages = $queue->receive(1);
                if (count($messages) > 0) {
                    foreach ($messages as $message) {
                        /* @var $message Aoe_Queue_Model_Message */
                        $message->execute();
                        $queue->deleteMessage($message);
                        if (empty($statistics[$queueName])) {
                            $statistics[$queueName] = 0;
                        }
                        $statistics[$queueName]++;
                        if ((microtime(true) - $starttime) > $maxRuntime) {
                            return $statistics;
                        }
                    }
                } else {
                    // $queue->deleteQueue();
                    unset($queues[$queueName]);
                }
            }
        }

        return $statistics;
    }
}
