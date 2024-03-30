<?php

namespace Notification\Api;

use Api\IOutput;

interface INotification {

	public function send($user, $message, $url);

}
