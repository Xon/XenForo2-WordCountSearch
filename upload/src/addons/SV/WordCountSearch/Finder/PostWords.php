<?php

namespace SV\WordCountSearch\Finder;

use SV\StandardLib\Helper;
use XF\Mvc\Entity\Finder;

class PostWords extends Finder
{
    public static function finder(): self
    {
        return Helper::finder(self::class);
    }
}