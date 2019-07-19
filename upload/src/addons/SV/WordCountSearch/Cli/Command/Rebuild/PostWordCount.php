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
	}
}