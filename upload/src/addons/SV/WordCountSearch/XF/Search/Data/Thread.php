<?php
/**
 * @noinspection PhpMissingReturnTypeInspection
 */

namespace SV\WordCountSearch\XF\Search\Data;

use SV\WordCountSearch\Repository\WordCount as WordCountRepo;
use XF\Search\MetadataStructure;

/**
 * @Extends \XF\Search\Data\Thread
 */
class Thread extends XFCP_Thread
{
    /**
     * @param bool $forView
     * @return array
     */
    public function getEntityWith($forView = false)
    {
        $get = parent::getEntityWith($forView);

        $get[] = 'FirstPost.Words';

        return $get;
    }

    /**
     * @param \XF\Entity\Thread $entity
     * @return array
     */
    protected function getMetaData(\XF\Entity\Thread $entity)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Thread $entity */
        $metadata = parent::getMetaData($entity);

        // The firstPost isn't indexed as a post but as the thread.

        /** @var \SV\WordCountSearch\XF\Entity\Post|null $post */
        $post = $entity->FirstPost;
        if ($post === null)
        {
            return $metadata;
        }

        $wordCountRepo = WordCountRepo::get();
        $wordCount = $post->RawWordCount;
        if ($wordCount !== 0)
        {
            if ($post->Words === null)
            {
                $post->rebuildWordCount($wordCount, true, false);
            }
        }

        if ($wordCountRepo->isThreadWordCountSupported())
        {
            $wordCount = $wordCountRepo->checkThreadmarkWordCountForRebuild($entity);
        }
        else
        {
            $wordCount = 0;
        }
        if ($wordCount !== 0)
        {
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
