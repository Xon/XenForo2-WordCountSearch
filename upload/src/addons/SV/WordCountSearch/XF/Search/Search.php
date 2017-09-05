<?php

namespace SV\WordCountSearch\XF\Search;


class Search extends XFCP_Search
{
    /**
     * XF doesn't allow this to be extended
     *
     * @return \XF\Search\Query\Query
     */
    public function getQuery()
    {
        $class = \XF::app()->extendClass('XF\Search\Query\Query');

        return new $class($this);
    }
}