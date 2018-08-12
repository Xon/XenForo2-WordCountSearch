<?php

namespace SV\WordCountSearch\SV\Threadmarks\Service\Threadmark;

use XF\Mvc\Entity\Entity;

/**
 * Extends \SV\Threadmarks\Service\Threadmark\Creator
 */
class Creator extends XFCP_Creator
{
    public function setContent(Entity $content)
    {
        // enqueue the Words entity before the Threadmark's entity to ensure the cascading save actually works as expected
        if (is_callable([$content, 'rebuildPostWordCount']))
        {
            /** @noinspection PhpUndefinedMethodInspection */
            $content->rebuildPostWordCount(null, false, false);
        }

        parent::setContent($content);
    }
}