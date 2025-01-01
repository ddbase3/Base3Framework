<?php declare(strict_types=1);

namespace Api;

interface IDisplay extends IOutput {

	/* Übergabe anzuzeigender Daten */
	public function setData($data);

}
