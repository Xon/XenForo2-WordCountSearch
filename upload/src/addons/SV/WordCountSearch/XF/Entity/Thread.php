<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XF\Entity;

use SV\WordCountSearch\Entity\IContainerWordCount;
use SV\WordCountSearch\Repository\WordCount as WordCountRepo;
use XF\Mvc\Entity\Structure;

/**
 * @Extends \XF\Entity\Thread
 *
 * COLUMNS
 * @property int|null $word_count
 * @property int|null $word_count_
 *
 * GETTERS
 * @property-read string    $WordCount
 * @property-read int|null  $RawWordCount
 * @property-read bool      $hasThreadmarks
 */
class Thread extends XFCP_Thread implements IContainerWordCount
{
    public function getWordContentType(): string
    {
        return 'post';
    }

    public function updateThreadmarkDataCache($rebuildThreadmarkData = false)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        parent::updateThreadmarkDataCache($rebuildThreadmarkData);

        $this->wordCountThreadmarkCacheRebuild();
    }

    protected function wordCountThreadmarkCacheRebuild(): void
    {
        $wordCount = WordCountRepo::get()->getThreadWordCountFromEntity($this);

        $this->set('word_count', $wordCount, ['forceSet' => true]);
        $this->clearCache('WordCount');
        $this->clearCache('RawWordCount');
        $this->clearCache('hasThreadmarks');
    }

    public function getWordCount(?int $categoryId = null): string
    {
        $categoryId = (int)$categoryId;
        if ($categoryId && $this->hasOption('hasThreadmarks'))
        {
            $wordCount = isset($this->threadmark_category_data[$categoryId]['word_count'])
                ? $this->threadmark_category_data[$categoryId]['word_count']
                : 0;
        }
        else
        {
            $wordCount = $this->word_count_;
        }

        return WordCountRepo::get()->roundWordCount((int)$wordCount);
    }

    public function getRawWordCount(): ?int
    {
        return $this->word_count_;
    }

    public function getHasThreadmarks(): bool
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return !empty($this->threadmark_count) && $this->canViewThreadmarks();
    }

    public function rebuildWordCount(bool $doSave = true, bool $searchUpdate = true): void
    {
        $oldWordCount = (int)$this->word_count;
        WordCountRepo::get()->rebuildContainerWordCount($this);
        $newWordCount = (int)$this->word_count;
        if ($doSave)
        {
            $this->saveIfChanged();
        }

        if ($searchUpdate && $newWordCount !== $oldWordCount)
        {
            \XF::runOnce(
                'searchIndex-' . $this->getEntityContentType() . $this->getEntityId(),
                function () {
                    \XF::app()->search()->index($this->getEntityContentType(), $this);
                }
            );
        }
    }

    protected function _saveCleanUp(array $newDbValues)
    {
        parent::_saveCleanUp($newDbValues);

        // This occurs after _postSave, and we are now allowed to write to entity variables as expected
        // note; rebuildWordCount calls $this->save(), so disable the option to prevent unexpected recursion
        if ($this->getOption('svTriggerWordCountRebuild'))
        {
            $this->setOption('svTriggerWordCountRebuild', false);
            $this->rebuildWordCount();
        }
    }

    protected function _postDeletePosts(array $postIds)
    {
        parent::_postDeletePosts($postIds);

        $db = $this->db();

        $db->delete('xf_post_words', 'post_id IN (' . $db->quote($postIds) . ')');
    }

    /**
     * @param Structure $structure
     * @return Structure
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->behaviors['XF:Indexable']['checkForUpdates'][] = 'word_count';
        $structure->columns['word_count'] = ['type' => self::UINT, 'default' => 0];

        $structure->getters['WordCount'] = ['getter' => 'getWordCount', 'cache' => true];
        $structure->getters['RawWordCount'] = ['getter' => 'getRawWordCount', 'cache' => true];
        $structure->getters['hasThreadmarks'] = ['getter' => 'getHasThreadmarks', 'cache' => true];

        $structure->options['hasWordCountSupport'] = true;
        $structure->options['svTriggerWordCountRebuild'] = null;

        return $structure;
    }
}