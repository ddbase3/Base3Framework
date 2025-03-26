<?php declare(strict_types=1);

namespace Base3\Xrm\Api;

use Base3\Api\IBase;

interface IXrmFilterModule extends IBase {

	/* Liefert 0, wenn das Filter-Modul nicht passt, ansonsten je größer, desto besser passend (Prioritäten) */
	public function match($xrm, $filter);

	/* wendet Filter an */
	public function getEntries($xrm, $filter, $idsonly = false);

}
