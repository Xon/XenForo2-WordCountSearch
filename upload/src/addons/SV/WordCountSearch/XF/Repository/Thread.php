<?php

namespace SV\WordCountSearch\XF\Repository;

/**
 * Class Thread
 *
 * @package SV\WordCountSearch\XF\Repository
 */
class Thread extends XFCP_Thread
{
    /**
     * @param $threadId
     */
    public function rebuildThreadWordCount($threadId)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Thread $thread */
        $thread = null;

        if ($threadId instanceof \XF\Entity\Thread)
        {
            $thread = $threadId;
            $threadId = $threadId->thread_id;
        }

        if (!$threadId)
        {
            return;
        }

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        $wordCount = $wordCountRepo->getThreadWordCount($threadId);

        if ($thread)
        {
            $thread->fastUpdate('word_count', $wordCount);
            $thread->clearCache('WordCount');
            $thread->clearCache('RawWordCount');
            $thread->clearCache('hasThreadmarks');
        }
        else
        {
            $this->db()->update('xf_thread', [
                'word_count' => $wordCount
            ], 'thread_id = ?', $threadId);
        }
    }
}