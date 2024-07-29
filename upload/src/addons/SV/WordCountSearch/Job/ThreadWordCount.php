<?php

namespace SV\WordCountSearch\Job;

use SV\StandardLib\Helper;
use SV\WordCountSearch\XF\Entity\Thread as ExtendedThreadEntity;
use XF\Entity\Thread as ThreadEntity;
use XF\Job\AbstractRebuildJob;

/**
 * Class ThreadWordCount
 *
 * @package SV\WordCountSearch\Job
 */
class ThreadWordCount extends AbstractRebuildJob
{
    protected $defaultData = [
        'threadmarks-only' => false,
    ];

    /**
     * @param int $start
     * @param int $batch
     * @return array
     */
    protected function getNextIds($start, $batch): array
    {
        $db = \XF::db();

        $addOns = \XF::app()->container('addon.cache');
        if (isset($addOns['SV/Threadmarks']) && $this->data['threadmarks-only'])
        {
            return $db->fetchAllColumn($db->limit(
                '
				SELECT thread_id
				FROM xf_thread
				WHERE thread_id > ? AND threadmark_count > 0
				ORDER BY thread_id
			', $batch
            ), $start);
        }

        return $db->fetchAllColumn($db->limit(
            '
            SELECT thread_id
            FROM xf_thread
            WHERE thread_id > ?
            ORDER BY thread_id
        ', $batch
        ), $start);
    }

    /**
     * @param int $id
     */
    protected function rebuildById($id): void
    {
        $thread = Helper::find(ThreadEntity::class, $id);
        if ($thread === null)
        {
            return;
        }

        /** @var ExtendedThreadEntity $thread */
        $thread->rebuildWordCount();
    }

    protected function getStatusType(): string
    {
        return \XF::phrase('svWordCountSearch_x_word_count', ['contentType' => \XF::app()->getContentTypePhrase('thread')])->render();
    }
}