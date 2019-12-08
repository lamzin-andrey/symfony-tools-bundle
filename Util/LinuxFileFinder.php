<?php
namespace Landlib\SymfonyToolsBundle\Util;

use Landlib\SymfonyToolsBundle\Util\IFileFinder;

class LinuxFileFinder implements IFileFinder {
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
		$sClassName = str_replace('\\', '\\\\', $sClassName);
		$sCmd = "grep --include=*.xml  --include=*.yaml --include=*.yml  -rn '" .  $sTargetDirectory . '\' -e \'' . $sClassName . '\'';
		exec($sCmd, $aOut);
		$aResult = [];
		foreach ($aOut as $sLine) {
			$sLine = trim($sLine);
			if (!$sLine) {
				continue;
			}
			$a = explode(':', $sLine);
			if (count($a) >= 3 && $a[0][0] == '/' && file_exists($a[0])) {
				$oItem = new \StdClass();
				$oItem->path = trim($a[0]);
				unset($a[0], $a[1]);
				$oItem->content = join(':', $a);
				$aResult[] = $oItem;
			}
		}
		return $aResult;
	}
	
}
