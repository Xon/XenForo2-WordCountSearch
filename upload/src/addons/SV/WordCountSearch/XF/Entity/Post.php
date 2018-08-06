<?php

namespace SV\WordCountSearch\XF\Entity;

use SV\WordCountSearch\Entity\PostWords;
use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\Post
 *
 * @property int       WordCount
 *
 * @property PostWords Words
 */
class Post extends XFCP_Post
{
    protected $_wordCount = null;

    /**
     * @return bool
     */
    protected function _getThreadmarkDataForWC()
    {
        if (is_callable([$this, '_getThreadmarkData']))
        {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->_getThreadmarkData();
        }
        return false;
    }

    /**
     * @throws \XF\PrintableException
     */
    public function _preSave()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        if ($this->isChanged('message') || $this->isInsert())
        {
            $this->_wordCount = $wordCountRepo->getTextWordCount($this->message);
        }

        if ($this->_wordCount)
        {
            $threadmark = $this->_getThreadmarkDataForWC();
            if ($threadmark || $wordCountRepo->shouldRecordPostWordCount($this->getEntityId(), $this->_wordCount))
            {
                /** @var PostWords $words */
                $words = $this->getRelationOrDefault('Words');
                $words->word_count = $this->_wordCount;
            }
            else
            {
                if (!empty($this->Words))
                {
                    $this->Words->delete();
                }
            }
        }

        parent::_preSave();
    }

    /**
     * @return int
     */
    public function getWordCount()
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
     * @throws \XF\PrintableException
     */
    public function rebuildPostWordCount()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        /** @var \SV\WordCountSearch\Entity\PostWords $words */
        $words = $this->getRelationOrDefault('Words');
        $words->word_count = $wordCountRepo->getTextWordCount($this->message);
        $words->save();
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
