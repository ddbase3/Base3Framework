<?php

namespace Xrm\Api;

use Api\IBase;

interface IXrmFilterModule extends IBase {

	/* Liefert 0, wenn das Filter-Modul nicht passt, ansonsten je größer, desto besser passend (Prioritäten) */
	public function match($xrm, $filter);

	/* wendet Filter an */
	public function getEntries($xrm, $filter, $idsonly = false);

}
