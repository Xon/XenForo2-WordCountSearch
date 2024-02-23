<?php

namespace SV\WordCountSearch\Job;

use XF\Job\AbstractJob;
use XF\Job\JobResult;

/**
 * Class PostWordCount
 *
 * @package SV\WordCountSearch\Job
 */
class PrunePostWordCount extends AbstractJob
{
    protected $defaultData = [
        'steps' => 0,
        'start' => 0,
        'batch' => 1000,
        'max'   => null,
    ];

    /**
     * @param float $maxRunTime
     * @return JobResult
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function run($maxRunTime): JobResult
    {
        $db = $this->app->db();
        $startTime = microtime(true);

        $this->data['steps']++;

        if (!isset($this->data['max']))
        {
            $this->data['max'] = (int)$db->fetchOne('SELECT MAX(post_id) FROM xf_post_words');
        }

        if (!$this->data['max'] || $this->data['start'] > $this->data['max'])
        {
            return $this->complete();
        }

        $batchSize = max($this->data['batch'], 10000);
        $done = 0;

        do
        {
            $start = $this->data['start'];
            $next = $start + $batchSize;
            $this->data['start'] = $next;

            $db->query('
                DELETE words
                FROM xf_post_words AS words
                LEFT JOIN xf_post AS post ON (words.post_id = post.post_id)
                WHERE words.post_id >= ? AND words.post_id < ? AND post.post_id IS NULL
            ', [$start, $next]);

            $done += $batchSize;

            if (microtime(true) - $startTime >= $maxRunTime)
            {
                break;
            }
        }
        while ($this->data['start'] <= $this->data['max']);

        $this->data['batch'] = $this->calculateOptimalBatch($this->data['batch'], $done, $startTime, $maxRunTime, 50000);

        return $this->resume();
    }

    public function getStatusMessage(): string
    {
        $actionPhrase = \XF::phrase('rebuilding');
        $typePhrase = \XF::phrase('svWordCountSearch_x_word_count', ['contentType' => \XF::app()->getContentTypePhrase('post')])->render();

        return sprintf('%s... %s (%s)', $actionPhrase, $typePhrase, $this->data['start']);
    }

    public function canCancel(): bool
    {
        return true;
    }

    public function canTriggerByChoice(): bool
    {
        return true;
    }
}