<?php

namespace SV\WordCountSearch\Finder;

use SV\StandardLib\Helper;
use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\AbstractCollection as AbstractCollection;
use SV\WordCountSearch\Entity\PostWords as PostWordsEntity;

/**
 * @method AbstractCollection<PostWordsEntity>|PostWordsEntity[] fetch(?int $limit = null, ?int $offset = null)
 * @method PostWordsEntity|null fetchOne(?int $offset = null)
 * @implements \IteratorAggregate<string|int,PostWordsEntity>
 * @extends Finder<PostWordsEntity>
 */
class PostWords extends Finder
{
    public static function finder(): self
    {
        return Helper::finder(self::class);
    }
}