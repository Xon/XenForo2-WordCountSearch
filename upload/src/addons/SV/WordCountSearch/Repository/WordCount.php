<?php

namespace SV\WordCountSearch\Repository;

use XF\Mvc\Entity\Repository;

class WordCount extends Repository
{
    function str_word_count_utf8($str)
    {
        // ref: http://php.net/manual/de/function.str-word-count.php#107363
        return count(preg_split('~[^\p{L}\p{N}\']+~u',$str));
    }

    public function hasRangeQuery()
    {
        return false;
    }

    public function pushWordCountInIndex()
    {
        if(self::$hasElasticSearch === null)
        {
            $this->hasRangeQuery();
        }
        return self::$hasElasticSearch;
    }

    public function getWordCountThreshold()
    {
        if(self::$hasElasticSearch === null)
        {
            $this->hasRangeQuery();
        }
        if (self::$hasMySQLSearch)
        {
            return 0;
        }
        return \XF::app()->options()->wordcountThreshold;
    }

    public function shouldRecordPostWordCount($postId, $wordCount)
    {
        if ($wordCount >= $this->getWordCountThreshold())
        {
            return true;
        }

        return false;
    }

    public function getTextWordCount($message)
    {
        $strippedText = $this->app()->stringFormatter()->stripBbCode($message, ['stripQuote' => true]);
        // remove non-visible placeholders
        $strippedText = str_replace('[*]', ' ', $strippedText);
        return $this->str_word_count_utf8($strippedText);
    }

    public function roundWordCount($WordCount)
    {
        $inexactWordCount = intval($WordCount);
        if (!$inexactWordCount)
        {
            return 0;
        }
        if ($inexactWordCount >= 1000000)
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
        else
        {
            $inexactWordCount = 10;
        }

        return $inexactWordCount;
    }
}