<?php

namespace Observation\Api;

interface IObservation {

	public function addObserver($name, $observer);
	public function removeObserver($name, $observer);
	public function notifyObservers($name, $notificationType = null, $notificationObject = null);

}
