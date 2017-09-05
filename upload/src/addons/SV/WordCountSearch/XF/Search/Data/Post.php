<?php

namespace SV\WordCountSearch\XF\Search\Data;


use SV\WordCountSearch\Entity\PostWords;
use XF\Mvc\Entity\Entity;
use XF\Search\IndexRecord;
use XF\Search\MetadataStructure;

class Post extends XFCP_Post
{
    public function getEntityWith($forView = false)
    {
        $get = parent::getEntityWith($forView);
        $get[] = 'Words';
        return $get;
    }

    protected function getMetaData(\XF\Entity\Post $entity)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Post $entity */
        /** @var IndexRecord $index */
        $metadata = parent::getMetaData($entity);

        $wordCountRepo = $this->getWordCountRepo();

        $wordCount = $entity->getRawWordCount();
        if (empty($entity->Words))
        {
            if ($wordCountRepo->shouldRecordPostWordCount($entity->getEntityId(), $wordCount))
            {
                /** @var PostWords $words */
                $words = $entity->getRelationOrDefault('Words');
                $words->word_count = $this->_wordCount;
                $words->save();
            }
        }

        if ($wordCount > 0)
        {
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