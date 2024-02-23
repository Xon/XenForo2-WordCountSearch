<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XF\Entity;

use SV\WordCountSearch\Entity\IContainerWordCount;
use SV\WordCountSearch\Entity\IContentWordCount;
use SV\WordCountSearch\Entity\PostWords;
use SV\Threadmarks\Entity\Threadmark;
use SV\WordCountSearch\Repository\WordCount as WordCountRepo;
use XF\Mvc\Entity\Structure;

/**
 * @Extends \XF\Entity\Post
 *
 * @property-read string $WordCount
 * @property-read int    $RawWordCount
 *
 * @property-read PostWords $Words
 */
class Post extends XFCP_Post implements  IContentWordCount
{
    public function hasWordCount(): bool
    {
        return $this->Words !== null && $this->Words->exists();
    }

    public function isValidContainerWordCountUpdate(): bool
    {
        if (!$this->isValidKey('Threadmark'))
        {
            return false;
        }

        /** @var Threadmark|null $threadmark */
        $threadmark = $this->get('Threadmark');
        if ($threadmark === null || !$threadmark->exists())
        {
            return false;
        }

        $defaultCategoryId = WordCountRepo::get()->getDefaultThreadmarkCategoryId();

        if ($threadmark->isChanged('threadmark_category_id') && $threadmark->getPreviousValue('threadmark_category_id') === $defaultCategoryId)
        {
            return true;
        }

        return $threadmark->threadmark_category_id === $defaultCategoryId;
    }

    public function getWordCount(): string
    {
        return WordCountRepo::get()->roundWordCount($this->RawWordCount);
    }

    public function getRawWordCount(): int
    {
        if ($this->Words !== null)
        {
            return $this->Words->word_count;
        }

        return WordCountRepo::get()->getTextWordCount($this->message);
    }

    public function rebuildWordCount(int $wordCount = null, bool $doSave = true, bool $searchUpdate = true): void
    {
        $changes = false;
        $wordCountRepo = WordCountRepo::get();

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
                    $this->app()->search()->index($this->getEntityContentType(), $this);
                }
            );
        }
    }

    protected $_wordCount = null;

    protected function _preSave()
    {
        if ($this->isChanged('message') || ($this->isInsert() && !$this->Words))
        {
            $this->_wordCount = WordCountRepo::get()->getTextWordCount($this->message);
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
                $threadmark = $this->isValidKey('Threadmark') ? $this->get('Threadmark') : null;
                if ($threadmark !== null)
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
