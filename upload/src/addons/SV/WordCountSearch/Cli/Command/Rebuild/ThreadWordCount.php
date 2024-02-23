<?php

namespace SV\WordCountSearch\Cli\Command\Rebuild;

use Symfony\Component\Console\Input\InputOption;
use XF\Cli\Command\Rebuild\AbstractRebuildCommand;

class ThreadWordCount extends AbstractRebuildCommand
{
    protected function getRebuildName()
    {
        return 'word-count-thread';
    }

    protected function getRebuildDescription()
    {
        return 'Rebuild thread word counts';
    }

    protected function getRebuildClass()
    {
        return 'SV\WordCountSearch:ThreadWordCount';
    }

    protected function configureOptions()
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