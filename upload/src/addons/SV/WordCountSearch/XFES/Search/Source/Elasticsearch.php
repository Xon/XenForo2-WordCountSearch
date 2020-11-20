<?php

namespace SV\WordCountSearch\XFES\Search\Source;

use XF\Search\Query;

/**
 * Extends \XFES\Search\Source\Elasticsearch
 */
class Elasticsearch extends XFCP_Elasticsearch
{
    /**
     * XF2.0/XF2.1 support
     *
     * @param Query\Query $query
     * @param int         $maxResults
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    protected function getDslFromQuery(Query\Query $query, $maxResults)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $dsl = parent::getDslFromQuery($query, $maxResults);
        $dsl = $this->rewriteDslForWordCountOrder($query, $dsl);

        return $dsl;
    }

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
     * XF2.2+ support
     *
     * @param Query\KeywordQuery $query
     * @param int                $maxResults
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function getKeywordSearchDsl(Query\KeywordQuery $query, $maxResults)
    {
        $dsl = parent::getKeywordSearchDsl($query, $maxResults);
        $dsl = $this->rewriteDslForWordCountOrder($query, $dsl);

        return $dsl;
    }
}