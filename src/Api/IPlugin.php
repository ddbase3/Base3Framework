<?php

namespace Api;

interface IPlugin {

	/* Jedes Plugin kann hierüber Initialisierungen durchführen, z.B. den ServiceLocator befüllen */
	public function init();

}
