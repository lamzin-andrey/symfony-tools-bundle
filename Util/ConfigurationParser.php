<?php
namespace Landlib\SymfonyToolsBundle\Util;

//Landlib\SymfonyToolsBundle\Util\WindowsFileFinder;
use Landlib\SymfonyToolsBundle\Util\LinuxFileFinder;
use Symfony\Component\Yaml\Parser AS YParser;

class ConfigurationParser {
	
	/** @param string $_sClassName full class name  for example 'FOS\UserBundle\Controller\GroupController' **/
	private $_sClassName = '';

	/** @param string $_sAlias alias for _sClassName **/
	private $_sAlias = '';

	/** @param array $_arguments */
	private $_arguments = [];
	
	/**
	 * @param string $sClassName full class name  for example 'FOS\UserBundle\Controller\GroupController'
	 * @param string $sTargetDirectory configuration files will search in this directory
	 * @return array of string service arguments aliases.
	*/
	public function getServiceArgumentAliasesList(string $sClassName, string $sTargetDirectory)
	{
		$this->_sClassName = $sClassName;
		$oFileFinder = new LinuxFileFinder();
		if ($this->_isEnvWindows()) {
			$oFileFinder = new WindowsFileFinder();
		}
		$aFilenames = $oFileFinder->search($sClassName, $sTargetDirectory);//TODO for Windows
		//Find file with <service id="ALIAS" class="$sClassName"> ... <argument type="service" id="ALIAS_X" > ...
		//or analogy yaml
		$sFilename = $this->_getServiceDefinitionFile($aFilenames);
		if ($sFilename) {
			if ($this->_isXml($sFilename)) {
				return $this->_buildServiceAliasListFromXmlFile($sFilename);
			}
			if ($this->_isYaml($sFilename)) {
				return $this->_arguments;
			}
		}
		return [];
	}
	/**
	 * @param string $sClassName full class name  for example 'FOS\UserBundle\Controller\GroupController'
	 * @param string $sTargetDirectory configuration files will search in this directory
	 * @return string service alias.
	 */
	public function getServiceAlias(string $sClassName, string $sTargetDirectory)
	{
		if ($this->_sAlias && $this->_sClassName == $sClassName) {
			return $this->_sAlias;
		}
		$this->_sAlias = '';
		$this->getServiceArgumentAliasesList($sClassName, $sTargetDirectory);
		return $this->_sAlias;
	}
	private function _buildServiceAliasListFromXmlFile(string $sFilename) : array
	{
		$oDoc = new \DOMDocument();
		$oDoc->validateOnParse = false;
		@$oDoc->loadHTML(file_get_contents($sFilename));
		$oNode = $oDoc->getElementById($this->_sAlias);
		if (!$oNode) {
			return [];
		}
		$arguments = $oNode->getElementsByTagName('argument');
		$aResult = [];
		for ($i = 0; $i < $arguments->length; $i++) {
			$oNode = $arguments->item($i);
			if ($oNode->hasAttribute('type') && $oNode->hasAttribute('id')) {
				if ($oNode->getAttribute('type') == 'service') {
					$aResult[] = '@' . $oNode->getAttribute('id');
				}
			} else if (!$oNode->hasAttribute('type') ){
				$aResult[] = $oNode->textContent;
			}
		}
		return $aResult;
	}
	/**
	 * @param array $aFilenames @see IFileFinder::search result
	 * @return string path to file with service definition
	*/
	private function _getServiceDefinitionFile(array $aFilenames) : string
	{
		$bServiceDefinitionIsFound = false;
		foreach ($aFilenames as $oSearchResult) {
			$sPath = $oSearchResult->path;
			if ($this->_isXml($sPath)) {
				$oDoc = new \DOMDocument();
				$oDoc->validateOnParse = false;
				$sXml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n<root>" . $oSearchResult->content . '</service></root>';
				@$oDoc->loadXML($sXml);
				$aTags = $oDoc->getElementsByTagName('service');
				for ($i = 0; $i < $aTags->length; $i++) {
					$oNode = $aTags->item($i);
					if ($oNode->getAttribute('class') == $this->_sClassName) {
						$this->_sAlias = $oNode->getAttribute('id');
						$bServiceDefinitionIsFound = true;
						break;
					}
				}
			}
			if ($this->_isYaml($sPath)) {
				$oYParser = new YParser();
				$aData = $oYParser->parseFile($sPath);
				if (isset($aData['services'])) {
					foreach ($aData['services'] as $sAlias => $aServiceInfo) {
						$sClass = ($aServiceInfo['class'] ?? '');
						if ($sClass == $this->_sClassName) {
							$this->_sAlias = $sAlias;
							$this->_arguments = ($aServiceInfo['arguments'] ?? []);
							$bServiceDefinitionIsFound = true;
							break;
						}
					}
				}
			}
			if ($bServiceDefinitionIsFound) {
				return $sPath;
			}
		}
		return '';
	}
	
	private function _isXml(string $sFilename) : bool
	{
		$aPathinfo = pathinfo($sFilename);
		$sExt = ($aPathinfo['extension'] ?? '');
		if ($sExt == 'xml') {
			return true;
		}
		return false;
	}
	private function _isYaml(string $sFilename) : bool
	{
		$aPathinfo = pathinfo($sFilename);
		$sExt = ($aPathinfo['extension'] ?? '');
		if ($sExt == 'yml' || $sExt == 'yaml') {
			return true;
		}
		return false;
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
