<?php

namespace SV\WordCountSearch\XF\Search\Data;

use XF\Search\MetadataStructure;

/**
 * Class Thread
 *
 * @package SV\WordCountSearch\XF\Search\Data
 */
class Thread extends XFCP_Thread
{
    /**
     * @param bool $forView
     *
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
     *
     * @return array
     * @throws \XF\PrintableException
     */
    protected function getMetaData(\XF\Entity\Thread $entity)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Thread $entity */
        $metadata = parent::getMetaData($entity);

        // The firstPost isn't indexed as a post but as the thread.

        /** @var \SV\WordCountSearch\XF\Entity\Post $post */
        $post = $entity->FirstPost;
        if (!$post)
        {
            return $metadata;
        }

        $wordCountRepo = $this->getWordCountRepo();

        if ($post->isValidRelation('Threadmark') && $post->getRelation('Threadmark'))
        {
            $wordCount = $post->RawWordCount;
            if ($wordCount)
            {
                $wordCount = intval($wordCount);
                if (!$post->Words)
                {
                    $post->rebuildPostWordCount($wordCount,true, false);
                }

                //$metadata['word_count'] = $wordCount;
            }
        }

        $threadmarkInstalled = $wordCountRepo->getIsThreadmarksSupportEnabled();
        $wordCount = $entity->RawWordCount;
        if ($threadmarkInstalled)
        {
            /** @var \SV\Threadmarks\XF\Entity\Thread $entity */
            $threadmarkCount = isset($entity->threadmark_category_data[1]) ? $entity->threadmark_category_data[1] : 0 ;
            if ($threadmarkCount && !$wordCount ||
                !$threadmarkCount && $wordCount)
            {
                /** @var \SV\WordCountSearch\XF\Repository\Thread $threadRepo */
                $threadRepo = \XF::app()->repository('XF:Thread');
                $threadRepo->rebuildThreadWordCount($entity);
                $wordCount = $entity->RawWordCount;
            }
        }
        if ($wordCount)
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

    /**
     * @param $order
     *
     * @return null
     */
    public function getTypeOrder($order)
    {
        return parent::getTypeOrder($order);
    }

    /**
     * @return \SV\WordCountSearch\Repository\WordCount|\XF\Mvc\Entity\Repository
     */
    protected function getWordCountRepo()
    {
        return \XF::app()->repository('SV\WordCountSearch:WordCount');
    }
}
