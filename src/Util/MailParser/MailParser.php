<?php

namespace Util\MailParser;

class MailParser {

	private $content;
	private $parts;

	public function __construct($filename = null) {
		if (!isset($filename)) return;
		$this->content = file_get_contents($filename);
		$this->parts = $this->parse($this->content);
	}

	public function fromString($content) {
		$this->content = $content;
		$this->parts = $this->parse($this->content);
	}

	private function parse($content) {
		$matches = array();
		// preg_match('#Content-Type: multipart\/[^;]+;\s*boundary="([^"]+)"#i', $content, $matches);
		preg_match('#Content-Type: multipart\/.+;\s*boundary="([^"]+)"#i', $content, $matches);
		// list($x, $boundary) = $matches;
		$boundary = array_pop($matches);
		if (isset($boundary) && !empty($boundary)) {
			$strippedcontent = substr($content, 0, strrpos($content, '--' . $boundary . '--'));
			$segments = explode('--' . $boundary, $strippedcontent);
			if (sizeof($segments) == 1) return new MailPart($content);
			$parts = array();
			foreach ($segments as $segment) {
				$part = $this->parse($segment);
				if ($part != null) $parts[] = $part;
			}
			return $parts;
		} else {
			return new MailPart($content);
		}
	}

	public function getParts() {
		return $this->parts;
	}

	public function toString($part = null, $depth = 0) {
		if ($part == null) $part = $this->parts;
		if (is_array($part)) {
			$str = str_replace(" ", "&nbsp;", str_pad("", $depth * 20, " ")) . "[--" . sizeof($part) . "--]\n";
			foreach ($part as $segment) $str .= $this->toString($segment, $depth + 1);
			return $str;
		} else {
			return
				str_replace(" ", "&nbsp;", str_pad("", $depth * 20, " ")) . " " . $part->toString() . "\n"
				. str_replace(" ", "&nbsp;", str_pad("", $depth * 20, " ")) . json_encode($part->getHeaders()) . "\n\n";
		}
	}

}
