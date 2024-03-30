<?php

namespace Util\MailParser;

class MailPart {

	private $content;

	private $headers = array();
	private $body = "";

	public function __construct($content) {
		$this->content = trim($content);
		$this->parse();
	}

	private function parse() {
		$lines = preg_split('/\R/', $this->content);
		$headerlines = array();
		$bodylines = array();
		$header = true;
		foreach ($lines as $line) {
			if (!$header) {
				$bodylines[] = $line;
				continue;
			}
			if (!strlen(trim($line))) {
				$header = false;
				continue;
			}
			if (in_array($line[0], array(" ", "\t")) && sizeof($headerlines)) {
				$headerlines[sizeof($headerlines) - 1] .= " " . trim($line);
				continue;
			}
			$colon = strpos($line, ":");
			if ($colon === false) {  // wahrscheinlich nie der Fall
				$header = false;
				$bodylines[] = $line;
				continue;
			}
			$headerlines[] = $line;
		}
		foreach ($headerlines as $line) $this->headers[] = array(
			"name" => trim(substr($line, 0, strpos($line, ":"))),
			"value" => trim(substr($line, strpos($line, ":") + 1))
		);
		$this->body = implode("\n", $bodylines);
	}

	public function isEmpty() {
		return !strlen($this->body);
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getBody() {
		return $this->body;
	}

	public function toString() {
		return "(Size: " . strlen($this->body) . ")";
	}

	public function output() {
		// header ...
	}

}
