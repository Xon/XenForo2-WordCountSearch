<?php

namespace SV\WordCountSearch\Job;

use XF\Job\AbstractRebuildJob;

/**
 * Class ThreadWordCount
 *
 * @package SV\WordCountSearch\Job
 */
class ThreadWordCount extends AbstractRebuildJob
{
    /**
     * @param $start
     * @param $batch
     *
     * @return array
     */
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        $addOns = \XF::app()->container('addon.cache');
        if (empty($addOns['SV/Threadmarks']))
        {
            return $db->fetchAllColumn($db->limit(
                "
				SELECT thread_id
				FROM xf_thread
				WHERE thread_id > ?
				ORDER BY thread_id
			", $batch
            ), $start);
        }
        else
        {
            return $db->fetchAllColumn($db->limit(
                "
				SELECT thread_id
				FROM xf_thread
				WHERE thread_id > ? and threadmark_count > 0
				ORDER BY thread_id
			", $batch
            ), $start);
        }
    }

    /**
     * @param $id
     */
    protected function rebuildById($id)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Thread $thread */
        $thread = $this->app->em()->find('XF:Thread', $id);
        if (!$thread)
        {
            return;
        }

        /** @var \SV\WordCountSearch\XF\Repository\Thread $threadRepo */
        $threadRepo = $this->app->repository('XF:Thread');
        $threadRepo->rebuildThreadWordCount($thread);
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('svWordCountSearch_post_word_count');
    }
}