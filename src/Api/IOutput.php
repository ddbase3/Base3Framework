<?php

namespace Api;

interface IOutput extends IBase {

	/* Liefert die Ausgabe in gewünschtem Format (PAGE, HTML, XML, JSON, CSV, ...) */
	public function getOutput($out = "html");

	/* Liefert die Syntax (zusätzliche GET/POST-Daten) sowie Infos (nur falls Debug-Modus an) */
	public function getHelp();

}
