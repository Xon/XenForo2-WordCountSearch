<?php

namespace SV\WordCountSearch\Entity;

/**
 * Extends \XF\Entity\Post
 *
 * @property int|null word_count
 * @property int|null word_count_
 *
 * @property string    WordCount
 * @property int|null  RawWordCount
 */
interface IContainerWordCount
{
    public function getWordCount(int $categoryId = null): string;

    /**
     * @return int|null
     */
    public function getRawWordCount();

    public function getWordContentType(): string;

    public function rebuildWordCount(int $wordCount = null, bool $doSave = true, bool$searchUpdate = true);
}