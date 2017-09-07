<?php

namespace SV\WordCountSearch\XFES\Search\Source;

use SV\WordCountSearch\XF\Search\Query\RangeMetadataConstraint;
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

    protected function applyMetadataConstraint(Query\MetadataConstraint $metadata, array &$filters, array &$filtersNot)
    {
        if ($metadata instanceof RangeMetadataConstraint)
        {
            $values = $metadata->getValues();

            switch ($metadata->getMatchType())
            {
                case RangeMetadataConstraint::MATCH_LESSER:
                    $filters[] = [
                        'range' => [$metadata->getKey() => [
                            "lte" => $values[0],
                        ]]
                    ];
                    return;
                case RangeMetadataConstraint::MATCH_GREATER:
                    $filters[] = [
                        'range' => [$metadata->getKey() => [
                            "gte" => $values[0],
                        ]]
                    ];
                    return;
                case RangeMetadataConstraint::MATCH_BETWEEN:
                    $filters[] = [
                        'range' => [$metadata->getKey() => [
                            "lte" => $values[0],
                            "gte" => $values[1],
                        ]]
                    ];
                    return;
            }
        }
        parent::applyMetadataConstraint($metadata, $filters, $filtersNo);
    }
}
