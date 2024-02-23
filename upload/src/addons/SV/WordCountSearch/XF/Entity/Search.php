<?php
/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */

namespace SV\WordCountSearch\XF\Entity;

use SV\WordCountSearch\Repository\WordCount as WordCountRepo;

/**
 * @Extends \XF\Entity\Search
 */
class Search extends XFCP_Search
{
    protected function formatConstraintValue(string $key, $value)
    {
        // ElasticSearch Essentials field
        if ($key === 'word_count_lower' || $key === 'word_count_upper')
        {
            return WordCountRepo::get()->roundWordCount((int)$value);
        }

        return parent::formatConstraintValue($key, $value);
    }
}