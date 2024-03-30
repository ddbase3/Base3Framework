<?php

namespace Observation\Api;

use Api\IBase;

interface IObservable extends IBase {

	public function addObserver($observer);
	public function removeObserver($observer);
	// protected function notifyObservers($notificationType);

}
