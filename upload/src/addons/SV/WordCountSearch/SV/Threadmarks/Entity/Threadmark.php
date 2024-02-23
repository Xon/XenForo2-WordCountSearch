<?php

namespace SV\WordCountSearch\SV\Threadmarks\Entity;

use SV\WordCountSearch\Entity\IContentWordCount;
use XF\Mvc\Entity\Structure;

/**
 * @Extends \SV\Threadmarks\Entity\Threadmark
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
        $content = $this->Content;
        if ($content !== null && $content->isValidGetter('WordCount'))
        {
            return $content->get('WordCount');
        }

        return '';
    }

    public function getRawWordCount(): int
    {
        $content = $this->Content;
        if ($content !== null && $content->isValidGetter('RawWordCount'))
        {
            return (int)$content->get('RawWordCount');
        }

        return 0;
    }

    /**
     * @param Structure $structure
     * @return Structure
     * @noinspection PhpMissingReturnTypeInspection
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['WordCount'] = ['getter' => 'getWordCount', 'cache' => true];
        $structure->getters['RawWordCount'] = ['getter' => 'getRawWordCount', 'cache' => true];

        return $structure;
    }
}