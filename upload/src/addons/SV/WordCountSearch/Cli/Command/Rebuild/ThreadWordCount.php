<?php

namespace SV\WordCountSearch\Cli\Command\Rebuild;

use SV\WordCountSearch\Job\ThreadWordCount as ThreadWordCountJob;
use Symfony\Component\Console\Input\InputOption;
use XF\Cli\Command\Rebuild\AbstractRebuildCommand;

class ThreadWordCount extends AbstractRebuildCommand
{
    protected function getRebuildName(): string
    {
        return 'word-count-thread';
    }

    protected function getRebuildDescription(): string
    {
        return 'Rebuild thread word counts';
    }

    protected function getRebuildClass(): string
    {
        return ThreadWordCountJob::class;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configureOptions(): void
    {
        $this
            ->addOption(
                'threadmarks-only',
                null,
                InputOption::VALUE_NONE,
                'Only consider threadmarked posts. Default: true'
            );
    }
}