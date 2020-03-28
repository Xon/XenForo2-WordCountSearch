<?php

namespace SV\WordCountSearch\SV\Threadmarks\Handler\Content;



/**
 * Extends \SV\Threadmarks\Handler\Content\Post
 */
class Post extends XFCP_Post
{
    public function getContentTypeWith($contentAlias = null, array $with = [])
    {
        $with[] = $contentAlias ? $contentAlias . '.Words' : 'Words';

        return parent::getContentTypeWith($contentAlias, $with);
    }
}