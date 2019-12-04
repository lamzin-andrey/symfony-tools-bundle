<?php
namespace Landlib\SymfonyToolsBundle\Command;

use Symfony\Component\Console\Question\ChoiceQuestion;

interface IFooBar {
	public function fooBar($nonf, ChoiceQuestion $oCk, string $boo = 'ra', App\Entity\Users $oUser = null, $k = 0, string $po = "opa") : bool;
}