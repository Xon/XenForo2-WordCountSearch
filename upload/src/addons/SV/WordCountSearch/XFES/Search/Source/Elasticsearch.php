<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XFES\Search\Source;

use XF\Search\Query;
use function strpos;

/**
 * Extends \XFES\Search\Source\Elasticsearch
 */
class Elasticsearch extends XFCP_Elasticsearch
{
    protected function rewriteDslForWordCountOrder(Query\Query $query, array $dsl)
    {
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

    /**
     * @param Query\KeywordQuery $query
     * @param int                $maxResults
     * @return array
     */
    public function getKeywordSearchDsl(Query\KeywordQuery $query, $maxResults)
    {
        $dsl = parent::getKeywordSearchDsl($query, $maxResults);
        $dsl = $this->rewriteDslForWordCountOrder($query, $dsl);

        return $dsl;
    }
}