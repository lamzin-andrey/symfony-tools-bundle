<?php

namespace Landlib\SymfonyToolsBundle\Command;

use Landlib\SymfonyToolsBundle\Util\ConsoleTools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

use Landlib\SymfonyToolsBundle\Util\ConfigurationParser;


class GetServiceAliasCommand extends Command
{

	/** @property string $_sAppRoot Symfony 3.4 application root */
	private $_sAppRoot = '';

	/** @property Symfony\Component\Console\Output\OutputInterface $_output  */
	private $_output = '';
	
	/** @property Symfony\Component\Console\Output\InputInterface $_input  */
	private $_input = '';

	/** @property string $_sClassName containts original class name  */
	private $_sClassName = '';


	// the name of the command (the part after "bin/console")
	protected static $defaultName = 'landlib:service-alias';


	protected function configure()
	{
		$this->setDescription('Get service alias will return service alias from third-party bundle')
			->setHelp('Enter full class name (for example FOS\UserBundle\Controller\ResettingController) and try get service alias.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->_output = $output;
		$this->_input = $input;

		$this->_sAppRoot = ConsoleTools::searchAppRoot();
		if (!$this->_sAppRoot) {
			ConsoleTools::showError('Not found Symfony application root directory. Unable find "symfony.lock" file, and folders "config", "bin", "public", "src", "templates"', $this->_output);
			return;
		}

		$this->_sClassName = $this->_showEnterMessage('Enter Enter full class name (for example FOS\UserBundle\Controller\ResettingController)');
		ConsoleTools::showText($this->_sClassName);
		$s = $this->_getServiceAlias();
		if (!$s) {
			ConsoleTools::showError("Unable get aliases for service \n'{$this->_sClassName}'\n\nMake sure, than service registred.\n");
			return;
		}
		$separator = "\n==================\n";
		ConsoleTools::showText("Your service alias: \n" . $separator);
		ConsoleTools::showText($s);
		ConsoleTools::showText($separator);
	}
	/**
	 * Generate Yaml service configuration for file config/services.yaml
	*/
	private function _getServiceAlias() : string
	{
		$oConfigurationParser = new ConfigurationParser();
		return $oConfigurationParser->getServiceAlias($this->_sClassName, $this->_sAppRoot);
	}
	/**
	 * Wrapper console input
	 * @param string $sMessage for example 'Enter a number'
	 * @param mixed $default = '' for example ['one', 'two', 'three'] or 'Billion'. If type of argument is array, default value always in zero item.
	 * @return string
	*/
	private function _showEnterMessage(string $sMessage, $default = '') : string
	{
		return ConsoleTools::showEnterMessage($sMessage, $this, $this->_input, $this->_output, $default);
	}

}
