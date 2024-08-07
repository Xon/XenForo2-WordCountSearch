<?php

namespace SV\WordCountSearch\Entity;

/**
 * @property-read string    $WordCount
 * @property-read int|null  $RawWordCount
 */
interface IContentWordCount
{
    public function getWordCount(): string;

    public function getRawWordCount(): int;

    public function hasWordCount(): bool;

    public function rebuildWordCount(?int $wordCount = null, bool $doSave = true, bool $searchUpdate = true): void;
}
