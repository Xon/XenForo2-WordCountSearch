<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XF\Pub\Controller;

use SV\SearchImprovements\XF\Search\Query\Constraints\RangeConstraint;
use XF\Http\Request;
use XF\Search\Query\Query;
use XF\Search\Query\SqlOrder;

/**
 * @Extends \XF\Pub\Controller\Search
 */
class Search extends XFCP_Search
{
    /**
     * @param array $data
     * @param array $urlConstraints
     * @return Query
     */
    protected function prepareSearchQuery(array $data, &$urlConstraints = [])
    {
        $query = parent::prepareSearchQuery($data, $urlConstraints);

        $urlConstraints['word_count'] = $urlConstraints['word_count'] ?? [];
        if (!is_array($urlConstraints['word_count']))
        {
            $urlConstraints['word_count'] = [];
        }
        $searchRequest = new Request(\XF::app()->inputFilterer(), $data, [], []);
        $input = $searchRequest->filter([
            'c.word_count.lower' => 'uint',
            'c.word_count.upper' => 'uint',
        ]);

        if (!empty($input['c.word_count.lower']) && !empty($input['c.word_count.upper']))
        {
            $query->withMetadata(new RangeConstraint('word_count', [
                $input['c.word_count.upper'],
                $input['c.word_count.lower']
            ], RangeConstraint::MATCH_BETWEEN));
        }
        else if (!empty($input['c.word_count.lower']))
        {
            unset($urlConstraints['word_count']['upper']);
            $query->withMetadata(new RangeConstraint('word_count', $input['c.word_count.lower'], RangeConstraint::MATCH_GREATER));
        }
        else if (!empty($input['c.word_count.upper']))
        {
            unset($urlConstraints['word_count']['lower']);
            $query->withMetadata(new RangeConstraint('word_count', $input['c.word_count.upper'], RangeConstraint::MATCH_LESSER));
        }
        else
        {
            unset($urlConstraints['word_count']['upper']);
            unset($urlConstraints['word_count']['lower']);
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