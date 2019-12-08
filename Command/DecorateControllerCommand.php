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

use Landlib\SymfonyToolsBundle\Util\ConfigurationParser;


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

	/** @property array $_aPublics containts items like: [
	 * 	'name' => foo,
	 * 'arguments' => 'ChoiceQuestion $oCk, string $boo = "ra"',
	 * 'argumentsForCall' =>'$oCk, $boo'
	 * 'returnType' : 'string'
	 * ]
	 */
	private $_aPublics = [];

	/** @property string $_sClassName containts original class name  */
	private $_sClassName = '';

	/** @property string $_extends containts original parent class name  */
	private $_extends = '';

	/** @property string $_implements containts original implements interfaces  */
	private $_implements = '';


	// the name of the command (the part after "bin/console")
	protected static $defaultName = 'landlib:decorate-controller';

	public function fooBar($nonf, ChoiceQuestion $oCk, string $boo = 'ra', App\Entity\Users $oUser = null, $k = 0, string $po = "opa") : bool
	{
		return  false;
	}

	public function barFoo($nonf)
	{

	}

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
		$nl = "\n";
		if (!$this->_bFileIsController) {
			$this->_showError('File ' . $nl . '"' . $this->_sTargetPhpFile . '"' . $nl . ' is not containts controller definition.');
			return;
		}

		$this->_generateDestFilename();

		if (file_exists($this->_sDestPhpFile)) {
			$s = $this->_sDestPhpFile;
			$this->_sDestPhpFile = $this->_showEnterMessage('Destination file name ' . $nl . '"' . $this->_sTargetPhpFile . '" ' . $nl . ' already exists. Overwrite? (Enter "yes" or new path for save exists file)');
			if ($this->_sDestPhpFile == 'yes') {
				$this->_sDestPhpFile = $s;
			}
		}
		$this->_generateDestFileContent();
		$this->_generateYamlConfigFragment();
		$separator = "==================\n";
		$this->_showText($separator);
		$this->_showText($this->_sYamlConfigFragment);
		$this->_showText($separator);
		$this->_showText("Remember to change the name of the controller in the routes or annotation file.");
	}
	/**
	 * Generate Yaml service configuration for file config/services.yaml
	 */
	private function _generateYamlConfigFragment()
	{
		$oConfigurationParser = new ConfigurationParser();
		$aList = $oConfigurationParser->getServiceArgumentAliasesList($this->_sClassName, $this->_sAppRoot);
		$aList[] = '@service_container';
		$sConfigTemplate = file_get_contents(__DIR__ . '/../Resources/assets/configservice.template.txt');
		$sConfigArgTemplate = file_get_contents(__DIR__ . '/../Resources/assets/configserviceargument.template.txt');

		$a = explode('\\', $this->_sClassName);
		$selfAlias = 'App\\Controller\\' . $a[count($a) - 1];
		$s = str_replace('{{self_alias}}', $selfAlias, $sConfigTemplate);
		$s = str_replace('{{target_alias}}', $oConfigurationParser->getServiceAlias($this->_sClassName, $this->_sAppRoot), $s);
		$sa = '';
		if (count($aList)) {
			$a = [];
			foreach ($aList as $sAlias) {
				$a[] = str_replace('{{arg}}', $sAlias, $sConfigArgTemplate);
			}
			$sa = join("\n", $a);
		}
		$s = str_replace('{{arguments}}', $sa, $s);
		$this->_sYamlConfigFragment = $s;
	}
	/**
	 * Parse php file. Get className, public funcitons list, set _bFileIsController, set _aUses, set _extends, set _implements
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
		$this->_sClassName = $sClass = '';

		$this->_aUses = [];
		$this->_aPublics = [];
		$this->_implements = '';
		$this->_extends = '';
		$this->_bFileIsController = false;
		$shortClassName = '';
		foreach ($ast as $oItem) {
			if (!$sNamespace && get_class($oItem) == 'PhpParser\Node\Stmt\Namespace_') {
				$m = $oItem;
				/** @var \PhpParser\Node\Stmt\Namespace_ $m */
				$sNamespace = join('\\', $m->name->parts);
				foreach ($m->stmts as $oStatement) {
					if (get_class($oStatement) == 'PhpParser\Node\Stmt\Use_') {
						$this->_appendUse($oStatement);
					}
					/** @var \PhpParser\Node\Stmt\Class_ $oStatement */
					if (!$sClass && get_class($oStatement) == 'PhpParser\Node\Stmt\Class_') {
						$s = $this->_buildType($oStatement->extends);
						if ($s) {
							$this->_extends = ' extends ' . trim($s);
						}

						if (is_array($oStatement->implements)) {
							$aBuf = [];
							foreach ($oStatement->implements as $oImpl) {
								$aBuf[] = trim($this->_buildType($oImpl));
							}
							$this->_implements = ' implements ' . join(', ', $aBuf);
						}
						$shortClassName = $oStatement->name;
						$this->_sClassName = $sClass = $sNamespace . '\\' . $oStatement->name;
						$this->_bFileIsController = (strpos($oStatement->name, 'Controller') !== false);
						$this->_grapPublicMethodsList($oStatement->stmts, $oStatement->name, $sClass);
					}
				}
			}
		}
		$this->_aUses[] = 'use ' . $this->_sClassName . ' as Base' . $shortClassName . ';';
		$this->_aUses[] = 'use Symfony\Component\DependencyInjection\ContainerInterface;';
		$this->_aUses[] = 'use \Symfony\Bundle\FrameworkBundle\Controller\AbstractController;';
	}
	/**
	 * Generate destination file name use _sTargetPhpFile and _sAppRoot values.
	 * set _sDestPhpFile
	*/
	private function _generateDestFilename()
	{
		$aInfo = pathinfo($this->_sTargetPhpFile);
		$sFolder = $this->_sAppRoot . '/src/Controller';
		if (!file_exists($sFolder)) {
			@mkdir($sFolder);
		}
		if (!file_exists($sFolder) || !is_dir($sFolder)) {
			throw new \Exception('Unable create folder "' . $sFolder . '"' . "\n");
		}
		$this->_sDestPhpFile = $this->_sAppRoot . '/src/Controller/' . $aInfo['basename'];
	}
	/**
	 * Use Resources/assets/class.template.txt file and _className, _aMethods fields
	 * Generate dest file.
	**/
	private function _generateDestFileContent()
	{
		//replace use section
		$sClassTemplate = file_get_contents(__DIR__ . '/../Resources/assets/class.template.txt');
		$sClassMethodTemplate = file_get_contents(__DIR__ . '/../Resources/assets/classmethod.template.txt');
		$s = str_replace('{{uses_section}}', join("\n", $this->_aUses), $sClassTemplate);

		//replace classname
		$aClassName = explode('\\', $this->_sClassName);
		$sClassName = $aClassName[count($aClassName) - 1];
		$s = str_replace('{{classname_section}}', $sClassName, $s);

		//replace constructor arguments and body
		$sConstructArgs = '';
		$sBody = '';
		$sMethods = '';
		$aMethods = [];
		foreach ($this->_aPublics as $aMethodInfo) {
			if ($aMethodInfo['name'] == '__construct') {
				$sConstructArgs = $aMethodInfo['arguments'];
				$sBody = $this->_createConstructBody($aMethodInfo['argumentsForCall']);
			} else {
				$sm = str_replace('{{methodname_section}}', $aMethodInfo['name'], $sClassMethodTemplate);
				$sm = str_replace('{{args_section}}', $aMethodInfo['arguments'], $sm);
				$sm = str_replace('{{call_args_section}}', $aMethodInfo['argumentsForCall'], $sm);
				$aMethods[] = $sm;
			}
		}
		$s = str_replace('{{constructor_argument_section}}', $sConstructArgs, $s);
		$s = str_replace('{{copy_args_to_fieldClassSection}}', $sBody, $s);

		//replace publicmethods_section
		$sMethods = join("\n", $aMethods);
		$s = str_replace('{{publicmethods_section}}', $sMethods, $s);
		file_put_contents($this->_sDestPhpFile, $s);
	}
	/**
	 * @see _generateDestFileContent
	 * @param array $argumentsForCall @see $this->>_aPublics item format
	 * @return string
	*/
	private function _createConstructBody(string $sArgumentsForCall) : string
	{
		$argumentsForCall = explode(', ', $sArgumentsForCall);
		foreach ($argumentsForCall as $s) {
			$sKey = str_replace('$', '', $s);
			$a[] = "\t\t\$this->_{$sKey} = {$s};";
		}
		return join("\n", $a);
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
	/***
	 * Append public methods info into $this->_aPublics
	 * @param array of \PhpParser\Node\Stmt\Stmt_ClassMethod_ $aClassItems
	 * @param string $shortClassName
	*/
	private function _grapPublicMethodsList(array $aClassItems, string $shortClassName) : void
	{
		/** @var \PhpParser\Node\Stmt\ClassMethod $oMethodInfo */
		foreach ($aClassItems as $oMethodInfo) {
			if (get_class($oMethodInfo)  == 'PhpParser\Node\Stmt\ClassMethod') {
				//1 - is a public
				if ($oMethodInfo->flags == 1) {
					$sMethodName = $this->_getClassName($oMethodInfo->name);
					if ($sMethodName == '__construct') {
						$oMethodInfo->params = $this->_addConstructorArguments($oMethodInfo->params, $shortClassName);
					}
					$oParsedParams = $this->_parseMethodArguments($oMethodInfo->params);
					$aItem = [
						'name' => $sMethodName,
						'arguments' => $oParsedParams->headerFormat,
	 					'argumentsForCall' => $oParsedParams->callFormat,
						'returnType' => $this->_getReturnTypeString($oMethodInfo->returnType)
					];
					$this->_aPublics[] = $aItem;

				}
			}
		}
	}
	/**
	 * add first arg  'BaseXXXController $oBaseController,' and last 'ContainerInterface : oContainer'
	 * @param array $aParams
	 * @param string $shortClassName
	 * @return array
	*/
	private function _addConstructorArguments(array $aParams, string $shortClassName) : array
	{
		$oArg = $this->_createArgumentObject('oBaseController', ['Base' . $shortClassName]);
		array_unshift($aParams, $oArg);
		$aParams[] = $this->_createArgumentObject('oContainer', ['ContainerInterface']);
		return $aParams;
	}
	/**
	 *  Create object compatible wuth \PhpParser\Node\Param
	 * @param string $sName
	 * @param array $aType
	 * @return StdClass {type: {}, var: {}, default: null, return : null}
	 */
	private function _createArgumentObject(string $sName, array $aType) : \StdClass
	{
		$oArg = new \StdClass();
		$oArg->type = new \StdClass();
		$oArg->type->parts = $aType;
		$oArg->var = new \StdClass();
		$oArg->var->name = $sName;
		$oArg->default = null;
		$oArg->return = null;
		return $oArg;
	}
	/**
	 * @param array of PhpParser\Node\Param $aParams
	 * @return StdClass {headerFormat : 'Foo $foo, $bar = 1', callFormat : '$foo, $bar'}
	*/
	private function _parseMethodArguments(array  $aParams) : \StdClass
	{
		$o = new \StdClass();
		$o->headerFormat = '';
		$o->callFormat = '';
		$aHeaderItems = [];
		$aCallItems = [];
		/** @var \PhpParser\Node\Param $oArg **/
		foreach ($aParams as $oArg) {
			$aHeaderItems[] = $this->_buildType($oArg->type) . '$' . $oArg->var->name . $this->_parseArgumentDefaultvalue($oArg->default);
			$aCallItems[] = '$' . $oArg->var->name;
		}
		$o->headerFormat = join(', ', $aHeaderItems);
		$o->callFormat = join(', ', $aCallItems);
		return $o;
	}
	/**
	 * @param ?\PhpParser\Node\Name $oType
	 * @return string with space in tail or empty string
	 */
	private function _buildType(/*?\PhpParser\Node\Name*/ $oType) : string
	{
		if ($oType && isset($oType->parts)) {
			$s = join('\\', $oType->parts) . ' ';
			if (count($oType->parts) > 1) {
				$s = '\\' . $s;
			}
			return $s;
		}
		return '';
	}
	/**
	 * @param ?\PhpParser\Node\Name $oType
	 * @return string with space in tail or empty string
	 */
	private function _parseArgumentDefaultValue(/*?\PhpParser\Node\Name*/ $oDefault) : string
	{
		if ($oDefault && isset($oDefault->value)) {
			if (get_class($oDefault) == 'PhpParser\Node\Scalar\String_') {
				return ' = \'' . $oDefault->value . '\'';
			}
			return ' = ' . $oDefault->value;
		}
		return '';
	}
	/**
	 * @param \PhpParser\Node\Identifier $oName
	 * @return string
	*/
	private function _getClassName(\PhpParser\Node\Identifier $oName) : string
	{
		return $oName->name;
	}
	/**
	 * @param $oReturnType
	 * @return string for example ' : void' or ' : int' or ''
	*/
	private function _getReturnTypeString($oReturnType)
	{
		if ($oReturnType && isset($oReturnType->name)) {
			return ' : ' . $oReturnType->name;
		}
		return '';
	}
}
