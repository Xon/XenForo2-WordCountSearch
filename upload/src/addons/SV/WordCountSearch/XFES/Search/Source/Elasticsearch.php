<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XFES\Search\Source;

use XF\Search\Query;

/**
 * Extends \XFES\Search\Source\Elasticsearch
 */
class Elasticsearch extends XFCP_Elasticsearch
{
    /**
     * @param Query\Query $query
     * @return array
     */
    protected function getSearchSortDsl(Query\Query $query)
    {
        $orderByClause = $query->getOrder();
        if ($orderByClause instanceof Query\SqlOrder &&
            $orderByClause->getOrder() === 'search_index.word_count DESC')
        {
            $query->orderedBy('date');

            return [
                ['word_count' => 'desc'],
                ['date' => 'desc'],
            ];
        }

        return parent::getSearchSortDsl($query);
    }
}