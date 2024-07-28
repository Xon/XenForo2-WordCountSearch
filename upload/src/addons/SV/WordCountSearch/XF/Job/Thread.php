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
     * @param int $id
     */
    protected function rebuildById($id)
    {
        parent::rebuildById($id);

        /** @var \SV\WordCountSearch\XF\Entity\Thread|null $thread */
        $thread = \SV\StandardLib\Helper::find(\XF\Entity\Thread::class, $id);
        if ($thread !== null)
        {
            $thread->rebuildWordCount();
        }
    }
}