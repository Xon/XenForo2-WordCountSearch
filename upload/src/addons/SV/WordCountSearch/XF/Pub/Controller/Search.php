<?php

namespace SV\WordCountSearch\XF\Pub\Controller;

use SV\WordCountSearch\XF\Search\Query\RangeMetadataConstraint;
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

        $searchRequest = new \XF\Http\Request($this->app->inputFilterer(), $data, [], []);
        $input = $searchRequest->filter([
            'c' => 'array',
            'c.word_count.lower' => 'uint',
            'c.word_count.upper' => 'uint',
        ]);

        if (!empty($input['c.word_count.lower']))
        {
            $query->withMetadata(new RangeMetadataConstraint('word_count', $input['c.word_count.lower'], RangeMetadataConstraint::MATCH_GREATER));
        }
        if (!empty($input['c.word_count.upper']))
        {
            $query->withMetadata(new RangeMetadataConstraint('word_count', $input['c.word_count.upper'], RangeMetadataConstraint::MATCH_LESSER));
        }

        $urlConstraints = array_merge($urlConstraints, $input['c']);

        if ($query->getOrderName() == 'word_count')
        {
            $query->orderedBy(new SqlOrder('search_index.word_count DESC'));
        }

        return $query;
    }
}