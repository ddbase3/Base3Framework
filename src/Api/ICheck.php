<?php

namespace Api;

interface ICheck {

	/* for servicelocator services, to check if it's usable */
	public function checkDependencies();

}
