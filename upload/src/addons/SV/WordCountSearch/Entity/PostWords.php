<?php

namespace SV\WordCountSearch\Entity;

use SV\WordCountSearch\XF\Entity\Post as PostEntity;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $post_id
 * @property int $word_count
 *
 * RELATIONS
 * @property-read ?PostEntity $Post
 */
class PostWords extends Entity
{
    /** @noinspection PhpMissingParentCallCommonInspection */
    public static function getStructure(Structure $structure): Structure
    {
        $structure->table = 'xf_post_words';
        $structure->shortName = 'SV\ContentRatings:PostWords';
        $structure->primaryKey = 'post_id';
        $structure->columns = [
            'post_id'    => ['type' => self::UINT],
            'word_count' => ['type' => self::UINT],
        ];
        $structure->getters = [];
        $structure->relations = [
            'Post' => [
                'entity'     => 'XF:Post',
                'type'       => self::TO_ONE,
                'conditions' => 'post_id',
                'primary'    => true
            ],
        ];

        return $structure;
    }
}