<?php declare(strict_types=1);

namespace Base3;

class MvcView {

	// Pfad zum Template
	private $path;
	// Name des Templates, in dem Fall das Standardtemplate.
	private $template = 'default';

	/**
	 * Enthält die Variablen, die in das Template eingebetet 
	 * werden sollen.
	 */
	private $_ = array();

	public function __construct($path = '.') {
		$this->setPath($path);
	}

	public function setPath($path = '.') {
		$this->path = rtrim($path, DIRECTORY_SEPARATOR);
	}

	/**
	 * Ordnet eine Variable einem bestimmten Schl&uuml;ssel zu.
	 *
	 * @param String $key Schlüssel
	 * @param String $value Variable
	 */
	public function assign($key, $value) {
		$this->_[$key] = $value;
	}


	/**
	 * Setzt den Namen des Templates.
	 *
	 * @param String $template Name des Templates.
	 */
	public function setTemplate($template = 'default'){
		$this->template = $template;
	}

	/**
	 * Das Template-File laden und zurückgeben
	 *
	 * @param string $tpl Der Name des Template-Files (falls es nicht vorher 
	 * 						über steTemplate() zugewiesen wurde).
	 * @return string Der Output des Templates.
	 */
	public function loadTemplate(){
		$tpl = $this->template;
		// Pfad zum Template erstellen & überprüfen ob das Template existiert.
		// TODO DIR_TPL nutzen
		$file = $this->path . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $tpl;  // . '.php';
		$exists = file_exists($file);

		if ($exists){
			// Der Output des Scripts wird n einen Buffer gespeichert, d.h.
			// nicht gleich ausgegeben.
			ob_start();
				
			// Das Template-File wird eingebunden und dessen Ausgabe in 
			// $output gespeichert.
			include $file;
			$output = ob_get_contents();
			ob_end_clean();
			
			// Output zurückgeben.
			return $output;
		}
		else {
			// Template-File existiert nicht-> Fehlermeldung.
			return 'Unable to find template';
		}
	}

	public function loadBricks($set, $language = "") {
		if (!strlen($language)) {
			$servicelocator = \Base3\ServiceLocator::getInstance();
			$language = $servicelocator->get('language')->getLanguage();
		}
		// TODO DIR_LANG nutzen
		$filename = $this->path . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $set . DIRECTORY_SEPARATOR . $language . ".ini";
		$bricks = parse_ini_file($filename, true);
		if (isset($this->_["bricks"])) $bricks = array_merge($this->_["bricks"], $bricks);
		$this->assign("bricks", $bricks);
	}

	public function getBricks($set) {
		if (!isset($this->_["bricks"]) || !isset($this->_["bricks"][$set])) return null;
		return $this->_["bricks"][$set];
	}
}
