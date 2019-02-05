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

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->app->repository('SV\WordCountSearch:WordCount');
        $threadmarkInstalled = $wordCountRepo->getIsThreadmarksSupportEnabled();
        if (!$threadmarkInstalled)
        {
            return [];
        }

        return $db->fetchAllColumn($db->limit(
            "
				SELECT thread_id
				FROM xf_thread
				WHERE thread_id > ? and threadmark_count > 0
				ORDER BY thread_id
			", $batch
        ), $start);
    }

    /**
     * @param $id
     *
     * @throws \XF\PrintableException
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