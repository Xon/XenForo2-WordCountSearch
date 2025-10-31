<?php

namespace SV\WordCountSearch\Repository;

use SV\StandardLib\Helper;
use SV\Threadmarks\XF\Entity\Forum as ThreadmarkForumEntity;
use SV\WordCountSearch\Entity\IContainerWordCount;
use SV\WordCountSearch\XF\Entity\Post;
use SV\WordCountSearch\XF\Entity\Thread as ExtendedThreadEntity;
use XF\Entity\Forum as ForumEntity;
use XF\Entity\Thread as ThreadEntity;
use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Repository;
use function round;
use function str_replace;
use function stripos;
use function substr;

/**
 * Class WordCount
 *
 * @package SV\WordCountSearch\Repository
 */
class WordCount extends Repository
{
    /** @var int */
    public const DEFAULT_THREADMARK_CATEGORY_ID = 1;

    public static function get(): self
    {
        return Helper::repository(self::class);
    }

    public function getDefaultThreadmarkCategoryId(): int
    {
        return self::DEFAULT_THREADMARK_CATEGORY_ID;
    }

    protected function str_word_count_utf8(string $str): int
    {
        // ref: http://php.net/manual/de/function.str-word-count.php#107363
        return count(preg_split('~[^\p{L}\p{N}\']+~u', $str, -1, PREG_SPLIT_NO_EMPTY));
    }

    public function hasRangeQuery(): bool
    {
        //\XF::app()->search()->getQuery();
        if (self::$hasElasticSearch === null)
        {
            self::$hasElasticSearch = false;
            self::$hasMySQLSearch = true;
        }

        return self::$hasElasticSearch || self::$hasMySQLSearch;
    }

    /** @var bool|null */
    protected static $hasElasticSearch = null;
    protected static $hasMySQLSearch   = true;

    protected function getWordCountThreshold(): int
    {
        return (int)(\XF::app()->options()->wordcountThreshold ?? 20);
    }

    public function shouldRecordPostWordCount(Post $post, int $wordCount): bool
    {
        if ($wordCount >= $this->getWordCountThreshold())
        {
            return true;
        }

        if ($post->isValidKey('Threadmark') && $post->get('Threadmark'))
        {
            return true;
        }

        return false;
    }

    public function getTextWordCount(string $message): int
    {
        $strippedText = \XF::app()->stringFormatter()->stripBbCode($message, ['stripQuote' => true]);
        // remove non-visible placeholders
        $strippedText = str_replace('[*]', ' ', $strippedText);

        return $this->str_word_count_utf8($strippedText);
    }

    public function roundWordCount(int $wordCount): string
    {
        $phrase = null;
        if ($wordCount >= 1000000000) // 1B
        {
            $inexactWordCount = round($wordCount / 1000000000, 0);
            $phrase = 'svWordCount_inexact_word_count.b';
        }
        else if ($wordCount >= 10000000) // 10M - 999M
        {
            $inexactWordCount = round($wordCount / 1000000, 0);
            $phrase = 'svWordCount_inexact_word_count.m';
            if ($inexactWordCount >= 1000)
            {
                $inexactWordCount = 1;
                $phrase = 'svWordCount_inexact_word_count.b';
            }
        }
        else if ($wordCount >= 1000000) // 1M - 9M
        {
            $inexactWordCount = round($wordCount / 1000000, 1); // allow 1 decimal place, ie 1.2m
            $phrase = 'svWordCount_inexact_word_count.m';
        }
        else if ($wordCount >= 10000) // 10K-999K
        {
            $inexactWordCount = round($wordCount / 1000, 0);
            $phrase = 'svWordCount_inexact_word_count.k';
            if ($inexactWordCount >= 1000)
            {
                $inexactWordCount = 1;
                $phrase = 'svWordCount_inexact_word_count.m';
            }
        }
        else if ($wordCount >= 1000) // 1K-9K
        {
            $inexactWordCount = round($wordCount / 1000, 1); // allow 1 decimal place, ie 1.2k
            $phrase = 'svWordCount_inexact_word_count.k';
        }
        else if ($wordCount >= 100)
        {
            $inexactWordCount = round($wordCount / 100, 1) * 100;
            if ($inexactWordCount >= 1000)
            {
                $inexactWordCount = 1;
                $phrase = 'svWordCount_inexact_word_count.k';
            }
        }
        else if ($wordCount >= 10)
        {
            $inexactWordCount = round($wordCount / 10, 1) * 10;
        }
        else
        {
            $inexactWordCount = (int)max(0, round($wordCount, 0));
        }

        $inexactWordCount = (string)$inexactWordCount;

        if ($phrase === null)
        {
            return $inexactWordCount;
        }

        $language = \XF::app()->language();
        $decimalPoint = $language['decimal_point'];
        if ($decimalPoint !== '.')
        {
            $inexactWordCount = str_replace('.', $decimalPoint, $inexactWordCount);
        }

        return \XF::phrase($phrase, [
            'number' => $inexactWordCount,
        ])->render();
    }

    public function checkThreadmarkWordCountForRebuild(ThreadEntity $thread): ?int
    {
        /** @var ExtendedThreadEntity $thread */
        if (!\XF::isAddOnActive('SV/Threadmarks'))
        {
            return $thread->RawWordCount;
        }

        $wordCount = (int)$thread->RawWordCount;

        $threadmarkCount = $this->getThreadWordCountFromEntity($thread);
        if ($threadmarkCount !== 0 && $wordCount === 0 ||
            $threadmarkCount === 0 && $wordCount !== 0)
        {
            $thread->rebuildWordCount(true, false);
        }

        return $thread->RawWordCount;
    }

    public function isThreadWordCountSupported(?Entity $parentContainer = null): bool
    {
        if (!\XF::isAddOnActive('SV/Threadmarks'))
        {
            return false;
        }

        if ($parentContainer instanceof ForumEntity)
        {
            /** @var ThreadmarkForumEntity $parentContainer */
            if (!$parentContainer->canViewThreadmarks())
            {
                return false;
            }
        }

        return true;
    }

    public function getThreadWordCountFromEntity(ThreadEntity $thread): int
    {
        $defaultCategoryId = $this->getDefaultThreadmarkCategoryId();

        return (int)($thread->threadmark_category_data[$defaultCategoryId]['word_count'] ?? 0);
    }

    public function getContainerWordCount(string $contentType, string $containerType, int $containerId): int
    {
        if (!\XF::isAddOnActive('SV/Threadmarks'))
        {
            return 0;
        }

        return intval(\XF::db()->fetchOne('
                SELECT IFNULL(SUM(post_words.word_count), 0)
                FROM xf_sv_threadmark AS threadmark 
                INNER JOIN xf_post_words AS post_words ON (post_words.post_id = threadmark.content_id AND threadmark.content_type = ?)
                WHERE threadmark.container_type = ?
                  AND threadmark.container_id = ?
                  AND threadmark.message_state = ?
                  AND threadmark.threadmark_category_id = ?
            ', [$contentType, $containerType, $containerId, 'visible', $this->getDefaultThreadmarkCategoryId()]));
    }

    /**
     * Note; does not save the entity!
     *
     * @param IContainerWordCount|Entity $container
     * @param bool                       $threadmarkSupport
     */
    public function rebuildContainerWordCount(Entity $container, bool $threadmarkSupport = true): void
    {
        if (!($container instanceof IContainerWordCount) || !$container->exists())
        {
            return;
        }

        $addOns = \XF::app()->container('addon.cache');
        if ($threadmarkSupport && isset($addOns['SV/Threadmarks']) && \is_callable([$container, 'updateThreadmarkDataCache']))
        {
            // calls getThreadmarkCategoryData/wordCountThreadmarkCacheRebuild
            $container->updateThreadmarkDataCache();

            return;
        }

        $wordCount = $this->getContainerWordCount($container->getWordContentType(), $container->getEntityContentType(), $container->getEntityId());
        $container->set('word_count', $wordCount);
        $container->clearCache('WordCount');
        $container->clearCache('RawWordCount');
        $container->clearCache('hasThreadmarks');
    }
}
