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
     * @param int $id
     */
    protected function rebuildById($id)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Thread $thread */
        $thread = \XF::app()->find('XF:Thread', $id);
        if ($thread !== null)
        {
            $thread->rebuildWordCount();
        }
    }

    protected function getStatusType()
    {
        return \XF::phrase('svWordCountSearch_x_word_count', ['contentType' => \XF::app()->getContentTypePhrase('thread')])->render();
    }
}