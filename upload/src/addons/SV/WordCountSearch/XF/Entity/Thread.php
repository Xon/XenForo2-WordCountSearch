<?php

namespace SV\WordCountSearch\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\Thread
 *
 * @property string    wordCount
 * @property int       rawWordCount
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
        return $wordCountRepo->roundWordCount($this->getRawWordCount());
    }

    /**
     * @return int
     */
    public function getRawWordCount()
    {
        //$this->word_count
        return 0;
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['word_count'] = ['type' => self::UINT, 'default' => null, 'nullable' => true];

        $structure->getters['wordCount'] = [
            'getter' => true,
            'cache' => true
        ];

        $structure->getters['rawWordCount'] = [
            'getter' => true,
            'cache' => true
        ];

        return $structure;
    }
}