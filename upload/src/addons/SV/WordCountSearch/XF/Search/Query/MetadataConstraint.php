<?php

namespace SV\WordCountSearch\XF\Search;

use XF\Search\Query\MetadataConstraint;
use XF\Search\Query\SqlConstraint;

class RangeMetadataConstraint extends MetadataConstraint
{
    const MATCH_LESSER  = -42;
    const MATCH_GREATER = -41;
    const MATCH_BETWEEN = -40;

    public function setMatchType($match)
    {
        switch($match)
        {
            case 'LESSER':
            case self::MATCH_LESSER:
                $this->matchType = self::MATCH_LESSER;
                break;

            case 'GREATER':
            case self::MATCH_GREATER:
                $this->matchType = self::MATCH_GREATER;
                break;

            case 'BETWEEN':
            case self::MATCH_BETWEEN:
                $this->matchType = self::MATCH_BETWEEN;
                break;

            default:
                parent::setMatchType($match);
                break;
        }
    }

    public function asSqlConstraint()
    {
        switch($this->matchType)
        {
            case self::MATCH_LESSER:
                return new SqlConstraint("search_index.{$this->key} < %d ", $this->values);
            case self::MATCH_GREATER:
                return new SqlConstraint("search_index.{$this->key} > %d ", $this->values);
            case self::MATCH_BETWEEN:
                return new SqlConstraint("search_index.{$this->key} > %d and search_index.{$this->key} < %d ", $this->values);
            default:
                return null;
        }
    }
}