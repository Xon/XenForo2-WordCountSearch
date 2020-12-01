<?php

namespace SV\WordCountSearch\SV\Threadmarks\Entity;

use SV\WordCountSearch\Entity\IContentWordCount;
use XF\Mvc\Entity\Structure;

/**
 * Extends \SV\Threadmarks\Entity\Threadmark
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
        if ($content && $content->isValidGetter('WordCount'))
        {
            return $content->get('WordCount');
        }

        return '';
    }

    /**
     * @param Structure $structure
     * @return Structure
     */
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);

        $structure->getters['WordCount'] = true;

        return $structure;
    }
}