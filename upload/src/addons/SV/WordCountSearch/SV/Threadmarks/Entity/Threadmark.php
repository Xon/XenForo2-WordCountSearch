<?php

namespace SV\WordCountSearch\SV\Threadmarks\Entity;

use SV\WordCountSearch\Entity\IContentWordCount;
use SV\WordCountSearch\Repository\WordCount as WordCountRepo;
use XF\Mvc\Entity\Structure;

/**
 * @Extends \SV\Threadmarks\Entity\Threadmark
 *
 * @property ?int $word_count
 * @property-read string $WordCount
 * @property-read int $RawWordCount
 */
class Threadmark extends XFCP_Threadmark
{
    protected function _postSave()
    {
        if ($this->isInsert())
        {
            $content = $this->Content;
            if ($content instanceof IContentWordCount && !$content->hasWordCount())
            {
                // handle case where a threadmark is added to content, and needs it's word-count rebuilt
                $content->rebuildWordCount();
            }
        }

        parent::_postSave();
    }

    public function getWordCount(): string
    {
        if ($this->word_count !== null)
        {
            return WordCountRepo::get()->roundWordCount($this->word_count);
        }

        $content = $this->Content;
        if ($content !== null && $content->isValidGetter('WordCount'))
        {
            return $content->get('WordCount');
        }

        return '';
    }

    public function getRawWordCount(): int
    {
        if ($this->word_count !== null)
        {
            return $this->word_count;
        }

        $content = $this->Content;
        if ($content !== null && $content->isValidGetter('RawWordCount'))
        {
            return (int)$content->get('RawWordCount');
        }

        return 0;
    }

    public function updateWordCount(int $wordCount): void
    {
        if (!$this->exists())
        {
            $this->set('word_count', $wordCount, ['forceSet' => true]);
        }
        else
        {
            $this->fastUpdate('word_count', $wordCount);
        }
        $this->_getterCache['RawWordCount'] = $wordCount;
        $this->clearCache('WordCount');
    }

    /**
     * @param Structure $structure
     * @return Structure
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->columns['word_count'] = ['type' => self::UINT, 'nullable' => true, 'default' => null];

        $structure->getters['WordCount'] = ['getter' => 'getWordCount', 'cache' => true];
        $structure->getters['RawWordCount'] = ['getter' => 'getRawWordCount', 'cache' => true];

        return $structure;
    }
}