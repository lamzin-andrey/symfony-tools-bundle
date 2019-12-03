<?php

namespace Landlib\SymfonyToolsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;


use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;


class DecorateControllerCommand extends Command
{
	
	/** @property string $_sYamlConfigFragment Fragment Yaml service configuration */
	private $_sYamlConfigFragment = '';
	
	/** @property string $_sAppRoot Symfony 3.4 application root */
	private $_sAppRoot = '';
	
	/** @property string $_sTargetPhpFile Php controller file from [third-party] bundle, which will overriden */
	private $_sTargetPhpFile = '';
	
	/** @property string $_sDestPhpFile Path to overriden php controller */
	private $_sDestPhpFile = '';
	
	/** @property Symfony\Component\Console\Output\OutputInterface $_output  */
	private $_output = '';
	
	/** @property Symfony\Component\Console\Output\InputInterface $_input  */
	private $_input = '';
	
	/** @property bool $_bFileIsController will true if _sTargetPhpFile containts controller definition. */
	private $_bFileIsController = false;

	/** @property array $_aUses containts 'use ... ; strings  */
	private $_aUses = [];


	// the name of the command (the part after "bin/console")
	protected static $defaultName = 'landlib:decorate-controller';

	protected function configure()
	{
		//$this->addArgument('testing', InputArgument::REQUIRED, 'Why?');
		$this->setDescription('Decorate-controller will decorated target  controller from third-party bundle')
			->setHelp('It help');
		
		
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->_output = $output;
		$this->_input = $input;
		$this->_bFileIsController = false;
		
		$this->_searchAppRoot();
		if (!$this->_sAppRoot) {
			$this->_showError('Not found Symfony application root directory. Unable find "symfony.lock" file, and folders "config", "bin", "public", "src", "templates"');
			return;
		}
		
		$this->_sTargetPhpFile = $this->_showEnterMessage('Enter path to php file with override controller');
		
		
		$this->_showText($this->_sTargetPhpFile);
		
		if (!file_exists($this->_sTargetPhpFile)) {
			$this->_showError('Invalid target file name. File "' . $this->_sTargetPhpFile . '" not found.');
			return;
		}
		$this->_parseTargetFile();
		
		/*if (!$this->_bFileIsController) {
			$this->_showError('File "' . $this->_sTargetPhpFile . '" is not containts controller definition.');
			return;
		}
		
		$this->_generateDestFilename();
		if (file_exists($this->_sDestPhpFile)) {
			$this->_sDestPhpFile = $this->_showEnterMessage('Destination file name "' . $this->_sTargetPhpFile . '" already exists. Overwrite?');
			return;
		}
		
		
		
		$this->_generateDestFileContent();
		
		$this->_generateYamlConfigFragment();
		
		$this->_showText($this->_sYamlConfigFragment);*/
		
	}
	/**
	 * Parse php file. Get className, public funcitons list, set _bIsController, set _aUses
	*/
	private function _parseTargetFile()
	{
		$s = file_get_contents($this->_sTargetPhpFile);
		
		$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
		try {
			$ast = $parser->parse($s);
		} catch (Error $error) {
			echo "Parse error: {$error->getMessage()}\n";
		}
		$sNamespace = '';
		$sClass = '';

		foreach ($ast as $oItem) {
			if (!$sNamespace && get_class($oItem) == 'PhpParser\Node\Stmt\Namespace_') {
				$m = $oItem;
				/** @var \PhpParser\Node\Stmt\Namespace_ $m */
				$sNamespace = join('\\', $m->name->parts);
				/*var_dump($m->stmts[13]);
				die;*/
				foreach ($m->stmts as $oStatement) {
					if (!$sClass && get_class($oStatement) == 'PhpParser\Node\Stmt\Use_') {
						$this->_appendUse($oStatement);
					}
					/** @var \PhpParser\Node\Stmt\Class_ $oStatement */
					if (!$sClass && get_class($oStatement) == 'PhpParser\Node\Stmt\Class_') {
						$sClass = $sNamespace . '\\' . $oStatement->name;
					}
				}
			}

			var_dump($this->_aUses);
			die;
		}


		$dumper = new NodeDumper();
		
		file_put_contents('/home/andrey/log.log', $dumper->dump($ast));
	}
	/**
	 * TODO
	 * Generate destination file name use _sTargetPhpFile and _sAppRoot values.
	 * set _sDestPhpFile
	*/
	private function _generateDestFilename()
	{
		
	}
	/**
	 * Use Resources/assets/class.template.txt file and _className, _aMethods fields
	 * Generate dest file.
	**/
	private function _generateDestFileContent()
	{
		
	}
	/**
	 * Generate Yaml service configuration for file aonfig/services.yaml
	*/
	private function _generateYamlConfigFragment()
	{
		
	}
	/**
	 * Output string in console
	*/
	private function _showText(string $sMessage)
	{
		echo "\n" . $sMessage . "\n";
	}
	/**
	 *  Detected root folder Symfony 3.4 application. Set _sAppRoot variable
	**/
	private function _searchAppRoot()
	{
		$aFiles = ['config', 'bin', 'public', 'src', 'templates', 'symfony.lock'];
		$nSz = count($aFiles);
		$sDirname = dirname(__FILE__);
		while (!$this->_checkDirAsAppRoot($sDirname, $aFiles, $nSz)) {
			$sDirname = dirname($sDirname);
			if (!$sDirname || $sDirname == '/') {
				$this->_sAppRoot = '';
				return;
			}
		}
		$this->_sAppRoot = $sDirname;
	}
	/**
	 * Show console message about error
	 * @param string $sDirname
	 * @param array $aFiles
	 * @param int $nSz
	 * @return bool
	*/
	private function _checkDirAsAppRoot(string $sDirname, array $aFiles, int $nSz) : bool
	{
		$aLs = scandir($sDirname);
		$nLength = count($aLs);
		$nControl = 0;
		for ($i = 0; $i < $nLength; $i++) {
			if (in_array($aLs[$i], $aFiles)) {
				$nControl++;
			}
		}
		return  ($nControl == $nSz);
	}
	/**
	 *
	 *  Show console message about error
	**/
	private function _showError(string $sMessage)
	{
		echo $this->_output->writeln('<error>' . $sMessage . '</error>');
	}
	/**
	 * Wrapper console input
	 * @param string $sMessage for example 'Enter a number'
	 * @param mixed $default = '' for example ['one', 'two', 'three'] or 'Billion'. If type of argument is array, default value always in zero item.
	 * @return string
	*/
	private function _showEnterMessage(string $sMessage, $default = '') : string
	{
		$oHelper = $this->getHelper('question');
		$oQuestion = null;
		if (is_array($default)) {
			$oQuestion = new ChoiceQuestion(
				$sMessage . '( default is ' . $default[0] . ')',
				$default,
				0
			);
		} else {
			$oQuestion = new Question($sMessage . "\n", $default);
		}
		return $oHelper->ask($this->_input, $this->_output, $oQuestion);
	}
	/***
	 * Append use ... ; string into $this->_aUses variable
	*/
	private function _appendUse(\PhpParser\Node\Stmt\Use_ $oStatement) : void
	{
		foreach ($oStatement->uses as $oUseUse) {
			if (isset($oUseUse->name) && isset($oUseUse->name->parts)) {
				$this->_aUses[] = 'use ' . join('\\', $oUseUse->name->parts) . ';';
			}
		}
	}
}
