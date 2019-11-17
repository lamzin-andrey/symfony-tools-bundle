<?php

namespace Landlib\SymfonyToolsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DecorateControllerCommand extends Command
{
	// the name of the command (the part after "bin/console")
	protected static $defaultName = 'landlib:decorate-controller';

	protected function configure()
	{
		$this->setDescription('Decorate-controller will decorated target  controller from third-party bundle')
			->setHelp('It help');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		echo 'I runned! ';
	}
}