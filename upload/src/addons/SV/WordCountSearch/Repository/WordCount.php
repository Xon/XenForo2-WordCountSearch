<?php

namespace SV\WordCountSearch\Repository;

use SV\WordCountSearch\XF\Entity\Post;
use XF\Mvc\Entity\Repository;

/**
 * Class WordCount
 *
 * @package SV\WordCountSearch\Repository
 */
class WordCount extends Repository
{
    /** @var int */
    const DEFAULT_THREADMARK_CATEGORY_ID = 1;

    public function getDefaultThreadmarkCategoryId()
    {
        return self::DEFAULT_THREADMARK_CATEGORY_ID;
    }

    /**
     * @param string $str
     * @return int
     */
    protected function str_word_count_utf8($str)
    {
        // ref: http://php.net/manual/de/function.str-word-count.php#107363
        return count(preg_split('~[^\p{L}\p{N}\']+~u',$str));
    }

    /**
     * @return bool
     */
    public function hasRangeQuery()
    {
        //$this->app()->search()->getQuery();
        if (self::$hasElasticSearch  === null)
        {
            self::$hasElasticSearch = false;
            self::$hasMySQLSearch = true;
        }

        return self::$hasElasticSearch || self::$hasMySQLSearch;
    }

    /** @var bool|null  */
    protected static $hasElasticSearch = null;
    protected static $hasMySQLSearch = true;

    /**
     * @return mixed
     */
    protected function getWordCountThreshold()
    {
        return \XF::app()->options()->wordcountThreshold;
    }

    /**
     * @param Post $post
     * @param int $wordCount
     * @return bool
     */
    public function shouldRecordPostWordCount($post, $wordCount)
    {
        if ($post->isValidRelation('Threadmark') && $post->getRelation('Threadmark'))
        {
            return true;
        }

        if ($wordCount >= $this->getWordCountThreshold())
        {
            return true;
        }

        return false;
    }

    /**
     * @param string $message
     * @return int
     */
    public function getTextWordCount($message)
    {
        $strippedText = $this->app()->stringFormatter()->stripBbCode($message, ['stripQuote' => true]);
        // remove non-visible placeholders
        $strippedText = str_replace('[*]', ' ', $strippedText);
        return $this->str_word_count_utf8($strippedText);
    }

    /**
     * @param int $WordCount
     * @return string
     */
    public function roundWordCount($WordCount)
    {
        $inexactWordCount = intval($WordCount);
        if (!$inexactWordCount)
        {
            return 0;
        }
        if ($inexactWordCount >= 1000000000)
        {
            $inexactWordCount = round($inexactWordCount / 1000000000, 1) . 'b';
        }
        else if ($inexactWordCount >= 1000000)
        {
            $inexactWordCount = round($inexactWordCount / 1000000, 1) . 'm';
        }
        else if ($inexactWordCount >= 100000)
        {
            $inexactWordCount = round($inexactWordCount / 100000, 1) * 100 . 'k';
        }
        else if ($inexactWordCount >= 10000)
        {
            $inexactWordCount = round($inexactWordCount / 10000, 1) * 10 . 'k';
        }
        else if ($inexactWordCount >= 1000)
        {
            $inexactWordCount = round($inexactWordCount / 1000, 1) . 'k';
        }
        else if ($inexactWordCount >= 100)
        {
            $inexactWordCount = round($inexactWordCount / 100, 1) * 100;
        }
        else if ($inexactWordCount >= 10)
        {
            $inexactWordCount = round($inexactWordCount / 10, 1) * 10;
        }
        else if ($inexactWordCount < 0)
        {
            $inexactWordCount = 0;
        }

        return strval($inexactWordCount);
    }

    /**
     * @param \XF\Entity\Thread|\SV\WordCountSearch\XF\Entity\Thread $thread
     * @return int|null
     */
    public function checkThreadmarkWordCountForRebuild(\XF\Entity\Thread $thread)
    {
        $addOns = \XF::app()->container('addon.cache');
        if (empty($addOns['SV/Threadmarks']))
        {
            return $thread->RawWordCount;
        }

        $wordCount = $thread->RawWordCount;

        $threadmarkCount = $this->getThreadWordCountFromEntity($thread);
        if ($threadmarkCount && !$wordCount ||
            !$threadmarkCount && $wordCount)
        {
            $this->rebuildThreadWordCount($thread);
        }

        return $thread->RawWordCount;
    }

    /**
     * @param \XF\Mvc\Entity\Entity|null $parentContainer
     * @return bool
     */
    public function isThreadWordCountSupported(\XF\Mvc\Entity\Entity $parentContainer = null)
    {
        $addOns = \XF::app()->container('addon.cache');
        if (empty($addOns['SV/Threadmarks']))
        {
            return false;
        }

        if ($parentContainer instanceof \XF\Entity\Forum)
        {
            /** @var \SV\Threadmarks\XF\Entity\Forum $parentContainer */
            if (!$parentContainer->canViewThreadmarks())
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \XF\Entity\Thread|\SV\Threadmarks\XF\Entity\Thread $thread
     * @return int|null
     */
    public function getThreadWordCountFromEntity(\XF\Entity\Thread $thread)
    {
        $defaultCategoryId = $this->getDefaultThreadmarkCategoryId();

        return isset($thread->threadmark_category_data[$defaultCategoryId]['word_count'])
            ? $thread->threadmark_category_data[$defaultCategoryId]['word_count']
            : 0;
    }

    /**
     * @param int $threadId
     * @return int
     */
    public function getThreadWordCount($threadId)
    {
        $addOns = \XF::app()->container('addon.cache');
        if (empty($addOns['SV/Threadmarks']))
        {
            return 0;
        }

        return intval($this->db()->fetchOne('
                SELECT IFNULL(SUM(post_words.word_count), 0)
                FROM xf_sv_threadmark AS threadmark 
                INNER JOIN xf_post_words AS post_words ON
                  (post_words.post_id = threadmark.content_id AND threadmark.content_type = ?)
                WHERE threadmark.container_type = ?
                  AND threadmark.container_id = ?
                  AND threadmark.message_state = ?
                  AND threadmark.threadmark_category_id = ?
            ', ['post', 'thread', $threadId, 'visible', $this->getDefaultThreadmarkCategoryId()]));
    }

    /**
     * @param $threadId
     */
    public function rebuildThreadWordCount($threadId)
    {
        /** @var \SV\WordCountSearch\XF\Entity\Thread $thread */
        $thread = null;

        if ($threadId instanceof \XF\Entity\Thread)
        {
            $thread = $threadId;
            $threadId = $threadId->thread_id;
        }

        if (!$threadId)
        {
            return;
        }

        /** @var \SV\WordCountSearch\Repository\WordCount $wordCountRepo */
        $wordCountRepo = $this->repository('SV\WordCountSearch:WordCount');
        $wordCount = $wordCountRepo->getThreadWordCount($threadId);

        if ($thread)
        {
            $thread->fastUpdate('word_count', $wordCount);
            $thread->clearCache('WordCount');
            $thread->clearCache('RawWordCount');
            $thread->clearCache('hasThreadmarks');
        }
        else
        {
            $this->db()->update('xf_thread', [
                'word_count' => $wordCount
            ], 'thread_id = ?', $threadId);
        }
    }
}
