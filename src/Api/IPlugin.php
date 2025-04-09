<?php declare(strict_types=1);

namespace Base3\Api;

interface IPlugin {

	public function __construct(IContainer $container);

	/* Jedes Plugin kann hierüber Initialisierungen durchführen, z.B. den ServiceLocator befüllen */
	public function init();
}
