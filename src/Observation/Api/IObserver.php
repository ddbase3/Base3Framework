<?php declare(strict_types=1);

namespace Observation\Api;

use Api\IBase;

interface IObserver extends IBase {

	public function notify($notificationType = null, $notificationObject = null);

}
