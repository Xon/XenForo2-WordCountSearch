<?php

namespace SV\WordCountSearch\XF\Entity;

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
class Thread extends XFCP_Thread
{
    public function updateThreadmarkCategoryData()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        parent::updateThreadmarkCategoryData();

        /** @var \SV\WordCountSearch\XF\Repository\Thread $threadRepo */
        $threadRepo = $this->repository('XF:Thread');
        $defaultCategoryId = $threadRepo->getDefaultCategoryId();

        $this->word_count = isset($this->threadmark_category_data[$defaultCategoryId]['word_count']) ? $this->threadmark_category_data[$defaultCategoryId]['word_count'] : 0;
        $this->clearCache('WordCount');
        $this->clearCache('RawWordCount');
        $this->clearCache('hasThreadmarks');
    }

    /**
     * @return string
     */
    public function getWordCount()
    {
        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        return $wordCountRepo->roundWordCount($this->word_count_);
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
        return !empty($this->threadmark_count);
    }

    /**
     * @param Structure $structure
     *
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->behaviors['XF:Indexable']['checkForUpdates'] = 'word_count';
        $structure->columns['word_count'] = ['type' => self::UINT, 'default' => 0];

        $structure->getters['WordCount'] = [
            'getter' => true,
            'cache' => true
        ];
        $structure->getters['RawWordCount'] = [
            'getter' => true,
            'cache' => false
        ];

        $structure->getters['hasThreadmarks'] = true;

        return $structure;
    }
}