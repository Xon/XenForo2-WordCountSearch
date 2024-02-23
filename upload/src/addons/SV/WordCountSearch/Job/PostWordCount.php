<?php

namespace SV\WordCountSearch\Job;

use SV\StandardLib\Helper;
use SV\WordCountSearch\XF\Entity\Post as ExtenedPostEntity;
use XF\Entity\Post as PostEntity;
use XF\Job\AbstractRebuildJob;

/**
 * Class PostWordCount
 *
 * @package SV\WordCountSearch\Job
 */
class PostWordCount extends AbstractRebuildJob
{
    protected $defaultData = [
        'threadmarks-only' => false,
        'rebuild' => false,
    ];

    /**
     * @param int $start
     * @param int $batch
     * @return array
     */
    protected function getNextIds($start, $batch): array
    {
        $db = $this->app->db();
        $sql = '';
        $where = '';

        $addOns = \XF::app()->container('addon.cache');
        if (isset($addOns['SV/Threadmarks']) && $this->data['threadmarks-only'])
        {
            if (!$this->data['rebuild'])
            {
                $sql = 'LEFT JOIN xf_post_words ON (xf_post_words.post_id = threadmark.content_id)';
                $where = ' AND xf_post_words.post_id IS NULL';
            }

            return $db->fetchAllColumn($db->limit(
                "
				SELECT threadmark.content_id 
				FROM xf_sv_threadmark as threadmark
				$sql
				WHERE threadmark.content_type = 'post' and threadmark.content_id > ? $where
				ORDER BY threadmark.content_id
			", $batch
            ), $start);
        }

        if (!$this->data['rebuild'])
        {
            $sql = 'LEFT JOIN xf_post_words ON (xf_post_words.post_id = post.post_id)';
            $where = ' AND xf_post_words.post_id IS NULL';
        }

        return $db->fetchAllColumn($db->limit(
            "
				SELECT post.post_id
				FROM xf_post as post
				$sql
				WHERE post.post_id > ? $where
				ORDER BY post.post_id
			", $batch
        ), $start);
    }

    protected function rebuildById($id): void
    {
        $post = Helper::find(PostEntity::class, $id);
        if ($post === null)
        {
            return;
        }

        /** @var ExtenedPostEntity $post */
        $post->rebuildWordCount();
    }

    protected function getStatusType(): string
    {
        return \XF::phrase('svWordCountSearch_x_word_count', ['contentType' => \XF::app()->getContentTypePhrase('post')])->render();
    }
}