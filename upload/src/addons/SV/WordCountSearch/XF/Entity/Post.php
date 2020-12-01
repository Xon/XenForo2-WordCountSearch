<?php

namespace SV\WordCountSearch\XF\Entity;

use SV\WordCountSearch\Entity\IContainerWordCount;
use SV\WordCountSearch\Entity\IContentWordCount;
use SV\WordCountSearch\Entity\PostWords;
use SV\Threadmarks\Entity\Threadmark;
use XF\Mvc\Entity\Structure;

/**
 * Extends \XF\Entity\Post
 *
 * @property string WordCount
 * @property int    RawWordCount
 *
 * @property PostWords Words
 */
class Post extends XFCP_Post implements  IContentWordCount
{
    public function hasWordCount(): bool
    {
        return $this->Words !== null && $this->Words->exists();
    }

    public function isValidContainerWordCountUpdate(): bool
    {
        if (!$this->isValidRelation('Threadmark'))
        {
            return false;
        }

        /** @var Threadmark $threadmark */
        $threadmark = $this->getRelation('Threadmark');
        if (!$threadmark || !$threadmark->exists())
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

    public function getWordCount(): string
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        return $wordCountRepo->roundWordCount($this->RawWordCount);
    }

    public function getRawWordCount(): int
    {
        if ($this->Words)
        {
            return $this->Words->word_count;
        }

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');

        return $wordCountRepo->getTextWordCount($this->message);
    }

    public function rebuildWordCount(int $wordCount = null, bool $doSave = true, bool $searchUpdate = true)
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
            if ($doSave && $words->hasChanges())
            {
                // Recording the memorized word count is vulnerable to a race condition, to migrate this
                // use "replace into" rather than "insert into"
                $words->useReplaceInto(true);
                $words->save();
            }
            $this->hydrateRelation('Words', $words);
        }
        else if ($this->Words)
        {
            if ($this->Words->exists())
            {
                $changes = true;
                $this->Words->reset();
                $this->Words->delete();
            }
            $this->clearCache('Words');
        }
        $this->clearCache('WordCount');
        $this->_getterCache['RawWordCount'] = (int)$wordCount;

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
        if ($this->Thread instanceof IContainerWordCount && $this->_wordCount !== null)
        {
            $this->rebuildWordCount($this->_wordCount, true, false);

            // the threadmark can be created on post-insert, only need to trigger a thread wordcount rebuild if the post is updated
            // if the threadmark is created when replying, updateThreadmarkDataCache/getThreadmarkCategoryData function updates the word-count as expected
            if ($this->isValidContainerWordCountUpdate())
            {
                // avoid updating the thread multiple times
                $threadmark = $this->isValidRelation('Threadmark') ? $this->getRelation('Threadmark') : null;
                if ($threadmark)
                {
                    $threadmark->setOption('update_container', false);
                }

                // if the thread has a save pending (that is editing first post + thread) punt the rebuild to the thread postSave()
                if ($this->Thread->_writePending)
                {
                    $this->Thread->setOption('svTriggerWordCountRebuild', true);
                }
                else
                {
                    $this->Thread->rebuildWordCount();
                }
            }
        }

        parent::_postSave();
    }

    protected function _postDelete()
    {
        parent::_postDelete();

        $this->db()->query('DELETE FROM xf_post_words WHERE post_id = ?', $this->post_id);
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['WordCount'] = ['getter' => 'getWordCount', 'cache' => true];
        $structure->getters['RawWordCount'] = ['getter' => 'getRawWordCount', 'cache' => true];

        $structure->relations['Words'] = [
            'entity'     => 'SV\WordCountSearch:PostWords',
            'type'       => self::TO_ONE,
            'conditions' => 'post_id',
            'primary'    => true
        ];
        $structure->defaultWith[] = 'Words';

        return $structure;
    }
}
