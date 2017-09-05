<?php

namespace SV\WordCountSearch\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\Thread
 *
 */
class Thread extends XFCP_Thread
{
    public function getWordCount()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        return $wordCountRepo->roundWordCount($this->getRawWordCount());
    }

    public function getRawWordCount()
    {
        //$this->word_count
        return 1000;
    }

    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['WordCount'] = [
            'getter' => true,
            'cache' => true
        ];

        return $structure;
    }
}