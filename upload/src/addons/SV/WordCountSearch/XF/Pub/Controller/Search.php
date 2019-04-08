<?php

namespace SV\WordCountSearch\XF\Pub\Controller;

use SV\SearchImprovements\XF\Search\Query\RangeMetadataConstraint;
use XF\Search\Query\SqlOrder;

/**
 * Extends \XF\Pub\Controller\Search
 *
 */
class Search extends XFCP_Search
{
    /**
     * @param array $data
     * @param array $urlConstraints
     *
     * @return \XF\Search\Query\Query
     */
    protected function prepareSearchQuery(array $data, &$urlConstraints = [])
    {
        $query = parent::prepareSearchQuery( $data, $urlConstraints);

        $searchRequest = new \XF\Http\Request($this->app->inputFilterer(), $data, [], []);
        $input = $searchRequest->filter([
            'c.word_count.lower' => 'uint',
            'c.word_count.upper' => 'uint',
        ]);

        if (!empty($input['c.word_count.lower']))
        {
            $query->withMetadata(new RangeMetadataConstraint('word_count', $input['c.word_count.lower'], RangeMetadataConstraint::MATCH_GREATER));
        }
        else
        {
            unset($urlConstraints['word_count']['lower']);
        }

        if (!empty($input['c.word_count.upper']))
        {
            $query->withMetadata(new RangeMetadataConstraint('word_count', $input['c.word_count.upper'], RangeMetadataConstraint::MATCH_LESSER));
        }
        else
        {
            unset($urlConstraints['word_count']['upper']);
        }

        if (empty($urlConstraints['word_count']))
        {
            unset($urlConstraints['word_count']);
        }

        if ($query->getOrderName() == 'word_count')
        {
            $query->orderedBy(new SqlOrder('search_index.word_count DESC'));
        }

        return $query;
    }
}