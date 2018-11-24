<?php

namespace SV\WordCountSearch\XFES\Search\Source;

use XF\Search\Query;

/**
 * Extends \XFES\Search\Source\Elasticsearch
 */
class Elasticsearch extends XFCP_Elasticsearch
{
    /**
     * @param Query\Query $query
     * @param             $maxResults
     * @return array
     */
    protected function getDslFromQuery(Query\Query $query, $maxResults)
    {
        $dsl = parent::getDslFromQuery($query, $maxResults);
        // rewrite order-by since getDslFromQuery is inflexible
        $orderByClause = $query->getOrder();
        if ($orderByClause instanceof Query\SqlOrder &&
            strpos($orderByClause->getOrder(), 'search_index.word_count') === 0)
        {
            $query->orderedBy('date');
            $dsl['sort'] = [
                ['word_count' => 'desc']
            ];
        }

        return $dsl;
    }
}