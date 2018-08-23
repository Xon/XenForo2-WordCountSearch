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

        if ($this->isChanged('message') || ($this->isInsert() && !$this->Words))
        {
            $this->_wordCount = $wordCountRepo->getTextWordCount($this->message);
        }

        if ($this->_wordCount)
        {
            $this->rebuildPostWordCount($this->_wordCount, false, false);
        }

        parent::_preSave();
    }


    protected function _postSave()
    {
        // the threadmark can be created on post-insert, only need to trigger a thread wordcount rebuild if the post is updated
        if ($this->_wordCount !== null && $this->isUpdate())
        {
            if ($this->isValidRelation('Threadmark') && $this->getRelation('Threadmark') && $this->Thread)
            {
                /** @var \SV\WordCountSearch\XF\Repository\Thread $threadRepo */
                $threadRepo = $this->app()->repository('XF:Thread');
                $threadRepo->rebuildThreadWordCount($this->Thread);
            }

            \XF::runOnce(
                'searchIndex-' . $this->getEntityContentType() . $this->getEntityId(),
                function () {
                    $this->app()->search()->index($this->getEntityContentType(), $this, true);
                }
            );
        }

        parent::_postSave();
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
        if ($this->Words)
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
     * @param bool     $searchUpdate
     * @throws \XF\PrintableException
     */
    public function rebuildPostWordCount($wordCount = null, $doSave = true, $searchUpdate = true)
    {
        $changes = false;
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        if ($wordCount === null)
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
                $changes = true;
                $words->saveIfChanged();
            }
        }
        else if ($this->Words)
        {
            $changes = true;
            $this->Words->delete();
        }
        $this->clearCache('WordCount');
        $this->clearCache('RawWordCount');

        if ($searchUpdate && $changes)
        {
            // word-count has changed, ensure search is updated!
            \XF::runOnce(
                'searchIndex-' . $this->getEntityContentType() . $this->getEntityId(),
                function () {
                    $this->app()->search()->index($this->getEntityContentType(), $this, true);
                }
            );
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
