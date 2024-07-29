<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XF\Search\Data;

use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;

/**
 * @Extends \XF\Search\Data\Post
 */
class Post extends XFCP_Post
{
    /**
     * @param bool $forView
     * @return array
     */
    public function getEntityWith($forView = false)
    {
        $get = parent::getEntityWith($forView);

        $get[] = 'Words';

        return $get;
    }

    /**
     * @param \XF\Entity\Post $entity
     * @return array
     */
    protected function getMetaData(\XF\Entity\Post $entity)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Post $entity */
        /** @var IndexRecord $index */
        $metadata = parent::getMetaData($entity);

        $wordCount = (int)$entity->RawWordCount;
        if ($wordCount !== 0)
        {
            if ($entity->Words === null)
            {
                $entity->rebuildWordCount($wordCount, true, false);
            }
            $metadata['word_count'] = $wordCount;
        }

        return $metadata;
    }

    /**
     * @param MetadataStructure $structure
     */
    public function setupMetadataStructure(MetadataStructure $structure)
    {
        parent::setupMetadataStructure($structure);
        $structure->addField('word_count', MetadataStructure::INT);
    }
}
