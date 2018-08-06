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
    /**
     * @param $start
     * @param $batch
     *
     * @return array
     */
    protected function getNextIds($start, $batch)
    {
        $db = $this->app->db();

        return $db->fetchAllColumn($db->limit(
            "
				SELECT post_id
				FROM xf_post
				WHERE post_id > ?
				ORDER BY post_id
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