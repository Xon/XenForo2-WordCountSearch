<?php

namespace SV\WordCountSearch\XF\Job;

use SV\StandardLib\Helper;
use XF\Entity\Thread as ThreadEntity;

/**
 * @Extends \XF\Job\Thread
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
        $thread = Helper::find(ThreadEntity::class, $id);
        if ($thread !== null)
        {
            $thread->rebuildWordCount();
        }
    }
}