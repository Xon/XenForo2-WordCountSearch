<?php

namespace SV\WordCountSearch\Job;

use XF\Job\AbstractRebuildJob;

/**
 * Class PostWordCount
 *
 * @package SV\WordCountSearch\Job
 */
class PostWordCount extends AbstractRebuildJob
{
    protected function setupData(array $data)
    {
        $this->defaultData = array_merge([
            'threadmarks-only' => false,
            'rebuild-counts' => true,
        ], $this->defaultData);

        return parent::setupData($data);
    }

    /**
     * @param $start
     * @param $batch
     *
     * @return array
     */
    protected function getNextIds($start, $batch)
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
				{$sql}
				WHERE threadmark.content_type = 'post' and threadmark.content_id > ? {$where}
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
				{$sql}
				WHERE post.post_id > ? {$where}
				ORDER BY post.post_id
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
        /** @var \SV\WordCountSearch\XF\Entity\Post $post */
        $post = $this->app->em()->find('XF:Post', $id);
        if (!$post)
        {
            return;
        }

        $post->rebuildPostWordCount();
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('svWordCountSearch_post_word_count');
    }
}