<?php

namespace SV\WordCountSearch\Cli\Command\Rebuild;

use Symfony\Component\Console\Input\InputOption;
use XF\Cli\Command\Rebuild\AbstractRebuildCommand;

class PostWordCount extends AbstractRebuildCommand
{
	protected function getRebuildName()
	{
		return 'word-count-post';
	}

	protected function getRebuildDescription()
	{
		return 'Rebuild post word counts';
	}

	protected function getRebuildClass()
	{
		return 'SV\WordCountSearch:PostWordCount';
	}

	protected function configureOptions()
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