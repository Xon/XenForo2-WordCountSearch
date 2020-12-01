<?php

namespace SV\WordCountSearch\XF\Search\Data;

use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;

/**
 * Class Post
 *
 * @package SV\WordCountSearch\XF\Search\Data
 */
class Post extends XFCP_Post
{
    /**
 * @param bool $forView
 *
 * @return array
 */
    public function getEntityWith($forView = false)
    {
        $get = parent::getEntityWith($forView);

        $get[] = 'Words';

        return $get;
    }

    /**
     * @param \XF\Entity\Post $post
     *
     * @return array
     */
    protected function getMetaData(\XF\Entity\Post $post)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Post $post */
        /** @var IndexRecord $index */
        $metadata = parent::getMetaData($post);

        $wordCount = (int)$post->RawWordCount;
        if ($wordCount)
        {
            if (!$post->Words)
            {
                $post->rebuildWordCount($wordCount, true, false);
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

    /**
     * @param $order
     *
     * @return null|\XF\Search\Query\SqlOrder
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
