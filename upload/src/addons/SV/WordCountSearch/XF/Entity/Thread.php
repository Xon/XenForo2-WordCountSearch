<?php

namespace SV\WordCountSearch\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\Thread
 *
 * COLUMNS
 * @property int|null word_count
 * @property int|null word_count_
 *
 * GETTERS
 * @property string    WordCount
 */
class Thread extends XFCP_Thread
{
    /**
     * @return string
     */
    public function getWordCount()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        return $wordCountRepo->roundWordCount($this->word_count_);
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['word_count'] = ['type' => self::UINT, 'default' => 0];

        $structure->getters['WordCount'] = [
            'getter' => true,
            'cache' => true
        ];

        return $structure;
    }
}