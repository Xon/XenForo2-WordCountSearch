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

        /** @var \SV\WordCountSearch\XF\Entity\Thread $thread */
        $thread = \XF::app()->find('XF:Thread', $id);
        if ($thread)
        {
            $thread->rebuildWordCount();
        }
    }
}