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
				SELECT post.post_id
				FROM xf_post as post
				LEFT JOIN xf_post_words ON (xf_post_words.post_id = post.post_id)
				WHERE post.post_id > ? AND xf_post_words.post_id IS NULL
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

    public function complete()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->app->repository('SV\WordCountSearch:WordCount');
        if ($wordCountRepo->isThreadWordCountSupported())
        {
            $this->app->jobManager()->enqueueUnique(
                'svWCSThreadWordCountRebuild',
                'SV\WordCountSearch:ThreadWordCount'
            );
        }

        return parent::complete();
    }

    /**
     * @return \XF\Phrase
     */
    protected function getStatusType()
    {
        return \XF::phrase('svWordCountSearch_post_word_count');
    }
}