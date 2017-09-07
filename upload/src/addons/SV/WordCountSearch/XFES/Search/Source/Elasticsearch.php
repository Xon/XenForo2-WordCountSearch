<?php

namespace SV\WordCountSearch\XFES\Search\Source;

use XF\Search\Query;

class Elasticsearch extends XFCP_Elasticsearch
{
    public function search(Query\Query $query, $maxResults)
    {
        $query = clone $query;
        return parent::search($query, $maxResults);
    }

    protected function getDslFromQuery(Query\Query $query, $maxResults)
    {
        $dsl = parent::getDslFromQuery($query, $maxResults);
        // rewrite order-by since getDslFromQuery is inflexible
        if ($query->getOrder() instanceof Query\SqlOrder)
        {
            $query->orderedBy('date');
            $dsl['sort'] = [
                ['word_count' => 'desc']
            ];
        }

        return $dsl;
    }
}
