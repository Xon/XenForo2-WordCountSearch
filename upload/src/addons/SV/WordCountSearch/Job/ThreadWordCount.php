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
    protected function setupData(array $data)
    {
        $this->defaultData = array_merge([
            'threadmarks-only' => false,
        ], $this->defaultData);

        return parent::setupData($data);
    }

    /**
     * @param $start
     * @param $batch
     * @return array
     */
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        $addOns = \XF::app()->container('addon.cache');
        if (isset($addOns['SV/Threadmarks']) && $this->data['threadmarks-only'])
        {
            return $db->fetchAllColumn($db->limit(
                "
				SELECT thread_id
				FROM xf_thread
				WHERE thread_id > ? AND threadmark_count > 0
				ORDER BY thread_id
			", $batch
            ), $start);
        }

        return $db->fetchAllColumn($db->limit(
            "
            SELECT thread_id
            FROM xf_thread
            WHERE thread_id > ?
            ORDER BY thread_id
        ", $batch
        ), $start);
    }

    /**
     * @param $id
     */
    protected function rebuildById($id)
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->app->repository('SV\WordCountSearch:WordCount');
        $wordCountRepo->rebuildThreadWordCount($id);

        $addOns = \XF::app()->container('addon.cache');
        if (isset($addOns['SV/Threadmarks']))
        {
            /** @var \SV\Threadmarks\XF\Entity\Thread $thread */
            $thread = \XF::app()->find('XF:Thread', $id);
            if($addOns['SV/Threadmarks'] >= 2010000)
            {
                $thread->updateThreadmarkDataCache();
            }
            else
            {
                /** @noinspection PhpUndefinedMethodInspection */
                $thread->updateThreadmarkCategoryData();
            }
        }
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('svWordCountSearch_post_word_count');
    }
}