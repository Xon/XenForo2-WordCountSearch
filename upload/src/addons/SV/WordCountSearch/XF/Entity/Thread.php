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
 * @property int|null  RawWordCount
 * @property bool      hasThreadmarks
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
     * @return int|null
     */
    public function getRawWordCount()
    {
        return $this->word_count_;
    }

    /**
     * @return bool
     */
    public function getHasThreadmarks()
    {
        return !empty($this->threadmark_count);
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
        $structure->getters['RawWordCount'] = [
            'getter' => true,
            'cache' => false
        ];

        $structure->getters['hasThreadmarks'] = true;

        return $structure;
    }
}