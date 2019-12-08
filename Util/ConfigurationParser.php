<?php
namespace Landlib\SymfonyToolsBundle\Util;

//Landlib\SymfonyToolsBundle\Util\WindowsFileFinder;
use Landlib\SymfonyToolsBundle\Util\LinuxFileFinder;

class ConfigurationParser {
	/**
	 * @param string $sClassName full class name  for example 'FOS\UserBundle\Controller\GroupController'
	 * @param string $sTargetDirectory configuration files will search in this directory
	 * @return array of string service arguments aliases. In zero item $sClassName service alias
	*/
	public function getServiceArgumentAliasesList(string $sClassName, string $sTargetDirectory)
	{
		$oFileFinder = new LinuxFileFinder();
		if ($this->_isEnvWindows()) {
			$oFileFinder = new WindowsFileFinder();
		}
		$aFilenames = $oFileFinder->search($sClassName, $sTargetDirectory);//TODO
		var_dump($aFilenames);
		die;
		//Найти файл, в котором именно <service id="ALIAS" class="$sClassName"> ... <argument type="service" id="ALIAS_X" > ... 
		//или аналог на yaml
		/*$sFilename = $this->_getServiceDefinitionFile($aFilenames);//TODO
		if ($this->_is_xml($sFilename)) { //TODO
			return $this->_buildServiceAliasListFromXmlFile($sFilename);
		}
		if ($this->_is_yaml()) { //TODO
			return $this->_buildServiceAliasListFromYamFile($sFilename); //TODO
		}*/
		return [];
	}
	/**
	 * 
	 * @return bool true if script run in OS Windows
	*/
	private function _isEnvWindows() : bool
	{
		$s = __DIR__;
		if (strpos($s, '/') === 0) {
			return false;
		}
		return true;
	}
}
