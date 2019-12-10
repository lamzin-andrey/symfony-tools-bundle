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
		//if (!preg_match("#.*/$#", $sTargetDirectory)) {
		//	$sTargetDirectory .= '/';
		//}
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
		$sCmd = 'findstr /I /S /C:"FOS\UserBundle\Controller\ChangePasswordController" D:\php-proj\hellos34\vendor\friendsofsymfony\user-bundle\Resources\config\*.xml';
		$sCmd = $this->_sFindBinary . ' /I /S /C:"' . $sClassName . '" ' . $sTargetDirectory . '\*.' . $sExtension;
		//echo "\n\n$sCmd\n\n";
		exec($sCmd, $aOut);
		
		$nSz = count($aOut);
		for ($i = 0; $i < $nSz; $i++) {
			$sLine = trim($aOut[$i]);
			if (!$sLine) {
				continue;
			}
			$a = explode(':', $sLine);
			$sPath = trim($a[0] . ':' . $a[1]);
			if (count($a) >= 3 && file_exists($sPath)) {
				$oItem = new \StdClass();
				$oItem->path = $sPath;
				unset($a[0], $a[1]);
				$oItem->content = trim(join(':', $a));
				$aResult[] = $oItem;
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
