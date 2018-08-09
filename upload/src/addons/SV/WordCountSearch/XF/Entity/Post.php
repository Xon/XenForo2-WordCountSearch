<?php

namespace SV\WordCountSearch\XF\Entity;

use SV\WordCountSearch\Entity\PostWords;
use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\Post
 *
 * @property string    WordCount
 * @property int|null  RawWordCount
 *
 * @property PostWords Words
 */
class Post extends XFCP_Post
{
    protected $_wordCount = null;

    /**
     * @throws \XF\PrintableException
     */
    protected function _preSave()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        if ($this->isChanged('message') || $this->isInsert())
        {
            $this->_wordCount = $wordCountRepo->getTextWordCount($this->message);
        }

        if ($this->_wordCount)
        {
            $this->rebuildPostWordCount($this->_wordCount, false);
        }

        parent::_preSave();
    }

    /**
     * @return string
     */
    public function getWordCount()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        return $wordCountRepo->roundWordCount($this->RawWordCount);
    }

    /**
     * @return int
     */
    public function getRawWordCount()
    {
        if (!empty($this->Words))
        {
            return $this->Words->word_count;
        }

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        return $wordCountRepo->getTextWordCount($this->message);
    }

    /**
     * @param int|null $wordCount
     * @param bool     $doSave
     * @throws \XF\PrintableException
     */
    public function rebuildPostWordCount($wordCount = null, $doSave = true)
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        if ($wordCount)
        {
            $wordCount = $wordCountRepo->getTextWordCount($this->message);
        }

        if ($wordCountRepo->shouldRecordPostWordCount($this, $wordCount))
        {
            /** @var PostWords $words */
            $words = $this->getRelationOrDefault('Words');
            $words->word_count = $wordCount;
            if ($doSave)
            {
                $words->saveIfChanged();
            }
        }
        else
        {
            if (!empty($this->Words))
            {
                $this->Words->delete();
            }
        }
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['WordCount'] = [
            'getter' => true,
            'cache' => true
        ];

        $structure->getters['RawWordCount'] = [
            'getter' => true,
            'cache' => true
        ];

        $structure->relations['Words'] = [
            'entity' => 'SV\WordCountSearch:PostWords',
            'type' => self::TO_ONE,
            'conditions' => 'post_id',
            'primary' => true
        ];
        $structure->defaultWith[] = 'Words';

        return $structure;
    }
}
