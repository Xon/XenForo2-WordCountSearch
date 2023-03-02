<?php
/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */

namespace SV\WordCountSearch\XF\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\Search
 */
class Search extends XFCP_Search
{
    protected function formatConstraintValue(string $key, $value)
    {
        // ElasticSearch Essentials field
        if ($key === 'word_count_lower' || $key === 'word_count_upper')
        {
            /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
            $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
            return $wordCountRepo->roundWordCount($value);
        }

        return parent::formatConstraintValue($key, $value);
    }
}