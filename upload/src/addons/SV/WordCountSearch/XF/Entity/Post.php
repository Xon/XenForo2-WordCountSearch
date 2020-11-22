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

    protected function _preSave()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        if ($this->isChanged('message') || ($this->isInsert() && !$this->Words))
        {
            $this->_wordCount = $wordCountRepo->getTextWordCount($this->message);
        }
        else
        {
            $this->_wordCount = null;
        }

        if ($this->_wordCount && $this->Words)
        {
            $this->Words->word_count = $this->_wordCount;
            $this->clearCache('WordCount');
            $this->clearCache('RawWordCount');
        }

        parent::_preSave();
    }


    protected function _postSave()
    {
        if ($this->Thread && $this->_wordCount !== null)
        {
            $this->rebuildPostWordCount($this->_wordCount, true, false);

            // the threadmark can be created on post-insert, only need to trigger a thread wordcount rebuild if the post is updated
            if ($this->isUpdate())
            {
                if ($this->isValidThreadWordCountUpdate())
                {
                    /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
                    $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
                    $wordCountRepo->rebuildThreadWordCount($this->Thread);
                }

                \XF::runOnce(
                    'searchIndex-' . $this->getEntityContentType() . $this->getEntityId(),
                    function () {
                        $this->app()->search()->index($this->getEntityContentType(), $this, true);
                    }
                );
            }
        }

        parent::_postSave();
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->db()->query('DELETE FROM xf_post_words WHERE post_id = ?', $this->post_id);
    }

    protected function isValidThreadWordCountUpdate()
    {
        if (!$this->isValidRelation('Threadmark'))
        {
            return false;
        }
        /** @var \SV\Threadmarks\Entity\Threadmark $threadmark */
        $threadmark = $this->getRelation('Threadmark');
        if (!$threadmark)
        {
            return false;
        }

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        $defaultCategoryId = $wordCountRepo->getDefaultThreadmarkCategoryId();

        if ($threadmark->isChanged('threadmark_category_id') && $threadmark->getPreviousValue('threadmark_category_id') === $defaultCategoryId)
        {
            return true;
        }

        return $threadmark->threadmark_category_id === $defaultCategoryId;
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
            $words = $this->getRelationOrDefault('Words', false);
            $words->word_count = $wordCount;
            $changes = $words->isChanged('word_count');
            if ($doSave && $changes)
            {
                $words->save();
            }
            $this->hydrateRelation('Words', $words);
        }
        else if ($this->Words)
        {
            $changes = true;
            if ($this->Words->exists())
            {
                $this->Words->reset();
                $this->Words->delete();
            }
            $this->clearCache('Words');
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
