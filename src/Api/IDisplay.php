<?php

namespace Api;

interface IDisplay extends IOutput {

	/* Übergabe anzuzeigender Daten */
	public function setData($data);

}
