<?php

namespace Landlib\SymfonyToolsBundle\Util;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;


class ConsoleTools
{
	/** @property OutputInterface $output */
	static public $output = null;
	/**
	 *  Detected root folder Symfony 3.4 application. Set _sAppRoot variable
	**/
	public static function searchAppRoot() : string
	{
		$aFiles = ['config', 'bin', 'public', 'src', 'templates', 'symfony.lock'];
		$nSz = count($aFiles);
		$sDirname = dirname(__FILE__);
		while (!static::_checkDirAsAppRoot($sDirname, $aFiles, $nSz)) {
			$sDirname = dirname($sDirname);
			if (!$sDirname || $sDirname == '/') {
				return '';
			}
		}
		return $sDirname;
	}
	/**
	 * Show console message about error
	 * @param string $sDirname
	 * @param array $aFiles
	 * @param int $nSz
	 * @return bool
	 */
	private static function _checkDirAsAppRoot(string $sDirname, array $aFiles, int $nSz) : bool
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
	 *  Show console message about error
	 * @param string $sMessage, $output
	 * @param OutputInterface $output
	 **/
	public static function showError(string $sMessage, $output = null)
	{
		if (!$output) {
			$output = static::$output;
		}
		if ($output) {
			echo $output->writeln('<error>' . $sMessage . '</error>');
		} else {
			echo ($sMessage . "\n");
		}
	}
	/**
	 *  Show console message about error
	 * @param string $sMessage, $output
	 * @param OutputInterface $output
	 **/
	public static function setOutputInterface($output)
	{
		static::$output = $output;
	}
	/**
	 * Wrapper console input
	 * @param string $sMessage for example 'Enter a number'
	 * @param Symfony\Component\Console\Command\Command $oContext
	 * @param InputInterface $oInput
	 * @param OutputInterface $output
	 * @param mixed $default = '' for example ['one', 'two', 'three'] or 'Billion'. If type of argument is array, default value always in zero item.
	 * @return string
	 */
	public static function showEnterMessage(string $sMessage, $oContext, $oInput, $output, $default = '') : string
	{
		$oHelper = $oContext->getHelper('question');
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
		return $oHelper->ask($oInput, $output, $oQuestion);
	}
	/**
	 * Output string in console
	 */
	public static  function showText(string $sMessage)
	{
		echo "\n" . $sMessage . "\n";
	}
}
