<?php

namespace SV\WordCountSearch\XF\Entity;

use SV\WordCountSearch\Entity\IContainerWordCount;
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

    protected function wordCountThreadmarkCacheRebuild()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        $wordCount = $wordCountRepo->getThreadWordCountFromEntity($this);

        $this->set('word_count', $wordCount, ['forceSet' => true]);
        $this->clearCache('WordCount');
        $this->clearCache('RawWordCount');
        $this->clearCache('hasThreadmarks');
    }

    public function getWordCount(int $categoryId = null): string
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

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        return $wordCountRepo->roundWordCount($wordCount);
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
        /** @noinspection PhpUndefinedMethodInspection */
        return !empty($this->threadmark_count) && $this->canViewThreadmarks();
    }

    public function rebuildWordCount(int $wordCount = null, bool $doSave = true, bool $searchUpdate = true)
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        $wordCountRepo->rebuildContainerWordCount($this);

        if ($searchUpdate)
        {
            \XF::runOnce(
                'searchIndex-' . $this->getEntityContentType() . $this->getEntityId(),
                function () {
                    $this->app()->search()->index($this->getEntityContentType(), $this, true);
                }
            );
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
     *
     * @return Structure
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

        return $structure;
    }
}