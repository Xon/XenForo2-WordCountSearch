<?php

namespace SV\WordCountSearch\Cli\Command\Rebuild;

use SV\WordCountSearch\Job\PostWordCount as PostWordCountJob;
use Symfony\Component\Console\Input\InputOption;
use XF\Cli\Command\Rebuild\AbstractRebuildCommand;

class PostWordCount extends AbstractRebuildCommand
{
    protected function getRebuildName(): string
    {
        return 'word-count-post';
    }

    protected function getRebuildDescription(): string
    {
        return 'Rebuild post word counts';
    }

    protected function getRebuildClass(): string
    {
        return PostWordCountJob::class;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configureOptions(): void
    {
        $this
            ->addOption(
                'threadmarks-only',
                null,
                InputOption::VALUE_NONE,
                'Only consider threadmarked posts. Default: false'
            );
        $this
            ->addOption(
                'rebuild',
                null,
                InputOption::VALUE_NONE,
                'Fully-rebuild word-count instead of just missing word counts. Default: false'
            );
    }
}