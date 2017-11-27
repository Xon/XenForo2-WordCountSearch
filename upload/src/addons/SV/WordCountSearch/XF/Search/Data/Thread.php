<?php

namespace SV\WordCountSearch\XF\Search\Data;


use SV\WordCountSearch\Entity\PostWords;
use XF\Mvc\Entity\Entity;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;

class Thread extends XFCP_Thread
{
    protected function getMetaData(\XF\Entity\Thread $entity)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Thread $entity */
        /** @var IndexRecord $index */
        $metadata = parent::getMetaData($entity);

        // The firstPost isn't indexed as a post but as the thread.

        /** @var \SV\WordCountSearch\XF\Entity\Post $post */
        $post = $entity->FirstPost;
        if (!$post)
        {
            return $metadata;
        }

        $wordCountRepo = $this->getWordCountRepo();

        $wordCount = $post->getRawWordCount();
        if ($wordCount !== null)
        {
            $wordCount = intval($wordCount);
            if (empty($post->Words))
            {
                if ($wordCountRepo->shouldRecordPostWordCount($post->getEntityId(), $wordCount))
                {
                    /** @var PostWords $words */
                    $words = $post->getRelationOrDefault('Words');
                    $words->word_count = $wordCount;
                    $words->save();
                }
            }

            $metadata['word_count'] = $wordCount;
        }

        return $metadata;
    }

    public function setupMetadataStructure(MetadataStructure $structure)
    {
        parent::setupMetadataStructure($structure);
        $structure->addField('word_count', MetadataStructure::INT);
    }


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
