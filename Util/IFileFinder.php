<?php

namespace Landlib\SymfonyToolsBundle\Util;

interface IFileFinder {
	/**
	 * @param string $sClassName full class name  for example 'FOS\UserBundle\Controller\GroupController'
	 * @param string $sTargetDirectory configuration files will search in this directory
	 * @return array of StdClass. {path: string, content: string }
	 * path - filename contains substr $sClassName
	 * content - found substring (from output of system command `grep` or `find`)
	*/
	public function search(string $sClassName, string $sTargetDirectory) : array;
}
