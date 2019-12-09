<?php
namespace Landlib\SymfonyToolsBundle\Util;

use Landlib\SymfonyToolsBundle\Util\IFileFinder;

class WindowsFileFinder implements IFileFinder {
	
	/** @param string $_sFindBinary path to system command find.exe in os windows (git for windows also containts find.exe and it may be conflict)*/
	private $_sFindBinary = '';
	
	/**
	 * @param string $sClassName full class name  for example 'FOS\UserBundle\Controller\GroupController'
	 * @param string $sTargetDirectory configuration files will search in this directory
	 * @return array of StdClass. {path: string, content: string }
	 * path - filename contains substr $sClassName
	 * content - found substring (from output of system command `grep` or `find`)
	*/
	public function search(string $sClassName, string $sTargetDirectory) : array
	{
		if (!preg_match("#.*/$#", $sTargetDirectory)) {
			$sTargetDirectory .= '/';
		}
		$aResult = [];
		$this->_search($aResult, $sClassName, $sTargetDirectory, 'xml');
		$this->_search($aResult, $sClassName, $sTargetDirectory, 'yml');
		$this->_search($aResult, $sClassName, $sTargetDirectory, 'yaml');
		return $aResult;
	}
	/**
	 * @param string $sFindBinary
	*/
	public function _search(array &$aResult, string $sClassName, string $sTargetDirectory, string $sExtension) : void
	{
		echo "Search {$sExtension} files...\n";
		$sCmd = 'echo off && for /R ' . $sTargetDirectory . ' %f in (.) do @' . $this->_sFindBinary . ' /i "' . $sClassName . '" %f\*.' . $sExtension . ' 2>null && echo on';
		exec($sCmd, $aOut);
		
		$nSz = count($aOut);
		for ($i = 0; $i < $nSz; $i++) {
			$sLine = trim($aOut[$i]);
			if (!$sLine) {
				continue;
			}
			$a = explode(':', $sLine);
			$sign = '---------- ';
			if (strpos($sLine, $sign) === 0) {
				$sNextLine = ($aOut[$i + 1] ?? '');
				if (strpos($sNextLine, $sClassName) !== false) {
					$oItem = new \StdClass();
					$oItem->path = trim(str_replace($sign, '', $sLine));
					$oItem->content = trim($sNextLine);
					$aResult[] = $oItem;
				}
				
			}
		}
	}
	/**
	 * @param string $sFindBinary
	*/
	public function setFindCmd(string $sFindBinary) : void
	{
		$this->_sFindBinary = $sFindBinary;
	}
	
}
