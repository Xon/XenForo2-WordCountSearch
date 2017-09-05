<?php

namespace SV\WordCountSearch\XF\Pub\Controller;

use XF\Search\Query\SqlOrder;

/**
 * Extends \XF\Pub\Controller\Search
 *
 */
class Search extends XFCP_Search
{
    protected function prepareSearchQuery(array $data, &$urlConstraints = [])
    {
        $query = parent::prepareSearchQuery( $data, $urlConstraints);

        if ($query->getOrderName() == 'word_count')
        {
            $query->orderedBy(new SqlOrder('search_index.word_count DESC'));
        }

        return $query;
    }
}