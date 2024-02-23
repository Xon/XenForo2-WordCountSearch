<?php

namespace SV\WordCountSearch\Entity;

/**
 * @property int|null word_count
 * @property int|null word_count_
 *
 * @property-read string    $WordCount
 * @property-read int|null  $RawWordCount
 */
interface IContainerWordCount
{
    public function getWordCount(int $categoryId = null): string;

    public function getRawWordCount(): ?int;

    public function getWordContentType(): string;

    public function rebuildWordCount(bool $doSave = true, bool $searchUpdate = true): void;
}