<?php

namespace Util\MailParser\Test;

use Page\Api\IPage;

class MailParserTest implements IPage {

	// Implementation of IBase

	public function getName() {
		return "mailparsertest";
	}

	// Implementation of IPage

        public function getUrl() {
                return $this->getName() . ".php";
        }

	// Implementation of IOutput

	public function getOutput($out = "html") {
		$mailfile = $_REQUEST["file"];
		$mp = new \Util\MailParser\MailParser($mailfile);
		$str = $mp->toString();
		return '<pre>' . $str . '</pre>';
	}

	public function getHelp() {
		return 'Help of MailParserTest' . "\n";
	}

}



/*
error_reporting(-1);
ini_set("display_errors", 1);

include("MailParser.php");
include("MailPart.php");

$mp = new MailParser("work2.eml");
echo $mp->toString();
*/