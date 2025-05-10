<?php declare(strict_types=1);

namespace Base3\Core;

use Base3\Api\IMvcView;
use Base3\Core\ServiceLocator;

class MvcView implements IMvcView {

	// Pfad zum Template
	private $path = '.';
	// Name des Templates, in dem Fall das Standardtemplate.
	private $template = 'default';

	/**
	 * Enthält die Variablen, die in das Template eingebetet werden sollen.
	 */
	private $_ = array();

	public function setPath(string $path = '.') {
		$this->path = rtrim($path, DIRECTORY_SEPARATOR);
	}

	/**
	 * Ordnet eine Variable einem bestimmten Schl&uuml;ssel zu.
	 *
	 * @param string $key Schlüssel
	 * @param mixed $value Variable
	 */
	public function assign(string $key, $value) {
		$this->_[$key] = $value;
	}


	/**
	 * Setzt den Namen des Templates.
	 *
	 * @param String $template Name des Templates.
	 */
	public function setTemplate(string $template = 'default') {
		$this->template = $template;
	}

	/**
	 * Das Template-File laden und zurückgeben
	 *
	 * @param string $tpl Der Name des Template-Files (falls es nicht vorher 
	 * 						über steTemplate() zugewiesen wurde).
	 * @return string Der Output des Templates.
	 */
	public function loadTemplate(): string {
		$tpl = $this->template;
		// Pfad zum Template erstellen & überprüfen ob das Template existiert.
		// TODO DIR_TPL nutzen
		$file = $this->path . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $tpl;  // . '.php';

		if (!file_exists($file)) {
			// Template-File existiert nicht -> Fehlermeldung.
			return 'Unable to find template';
		}

		// Der Output des Scripts wird n einen Buffer gespeichert, d.h. nicht gleich ausgegeben.
		ob_start();
				
		// Das Template-File wird eingebunden und dessen Ausgabe in $output gespeichert.
		include $file;
		$output = ob_get_contents();
		ob_end_clean();
			
		// Output zurückgebe
		return $output;
	}

	public function loadBricks(string $set, string $language = '') {
		if (!strlen($language)) {
			$servicelocator = ServiceLocator::getInstance();
			$language = $servicelocator->get('language')->getLanguage();
		}
		$filename = $this->path . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $set . DIRECTORY_SEPARATOR . $language . ".ini";
		$bricks = parse_ini_file($filename, true);
		if (isset($this->_["bricks"])) $bricks = array_merge($this->_["bricks"], $bricks);
		$this->assign("bricks", $bricks);
	}

	public function getBricks(string $set): ?array {
		if (!isset($this->_["bricks"]) || !isset($this->_["bricks"][$set])) return null;
		return $this->_["bricks"][$set];
	}
}
