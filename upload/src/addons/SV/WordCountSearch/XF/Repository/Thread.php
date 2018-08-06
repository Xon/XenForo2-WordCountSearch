<?php

namespace SV\WordCountSearch\XF\Repository;

/**
 * Class Thread
 *
 * @package SV\WordCountSearch\XF\Repository
 */
class Thread extends XFCP_Thread
{
    /** @var int */
    const DEFAULT_THREADMARK_CATEGORY_ID = 1;

    /**
     * @param $threadId
     */
    public function rebuildThreadWordCount($threadId)
    {
        $wordCount = 0;
        $forum = null;

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        $threadmarkInstalled = $wordCountRepo->getIsThreadmarksSupportEnabled();

        if ($threadId instanceof \XF\Entity\Thread)
        {
            $threadId = $threadId->thread_id;
        }

        if (!$threadId)
        {
            return;
        }

        $db = $this->db();

        if ($threadmarkInstalled)
        {
            $wordCount = intval($db->fetchOne('
                SELECT IFNULL(SUM(post_words.word_count), 0)
                FROM xf_sv_threadmark AS threadmark 
                INNER JOIN xf_post_words AS post_words ON
                  (post_words.post_id = threadmark.content_id AND threadmark.content_type = ?)
                WHERE threadmark.container_type = ?
                  AND threadmark.container_id = ?
                  AND threadmark.message_state = ?
                  AND threadmark.threadmark_category_id = ?
            ', ['post', 'thread', $threadId, 'visible', self::DEFAULT_THREADMARK_CATEGORY_ID]));
        }

        $db->update('xf_thread', [
            'word_count' => $wordCount
        ], 'thread_id = ?', $threadId);
    }
}