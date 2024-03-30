<?php

namespace Observation\Api;

use Api\IBase;

interface IObserver extends IBase {

	public function notify($notificationType = null, $notificationObject = null);

}
