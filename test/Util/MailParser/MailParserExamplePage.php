<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Util\MailParser;

use Base3\CoreFoundation\Page\Api\IPage;

class MailParserExamplePage implements IPage {

	// Implementation of IBase

	public static function getName(): string {
		return "mailparserexamplepage";
	}

	// Implementation of IPage

        public function getUrl() {
                return $this->getName() . ".php";
        }

	// Implementation of IOutput

	public function getOutput(string $out = 'html', bool $final = false): string {
		$mailfile = $_REQUEST["file"];
		$mp = new \Base3\Core\Util\MailParser\MailParser($mailfile);
		$str = $mp->toString();
		return '<pre>' . $str . '</pre>';
	}

	public function getHelp(): string {
		return 'Help of MailParserExamplePage' . "\n";
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
