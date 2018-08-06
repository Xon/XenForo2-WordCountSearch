<?php

namespace SV\WordCountSearch\XF\Job;

/**
 * Class Thread
 *
 * @package SV\WordCountSearch\XF\Job
 */
class Thread extends XFCP_Thread
{
    /**
     * @param $id
     */
    protected function rebuildById($id)
    {
        parent::rebuildById($id);

        /** @var \XF\Entity\Thread $thread */
        $thread = $this->app->em()->find('XF:Thread', $id);
        if (!$thread)
        {
            return;
        }

        /** @var \SV\WordCountSearch\XF\Repository\Thread $threadRepo */
        $threadRepo = $this->app->repository('XF:Thread');
        $threadRepo->rebuildThreadWordCount($thread);
    }
}