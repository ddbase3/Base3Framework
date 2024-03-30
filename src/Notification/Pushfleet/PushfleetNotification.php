<?php

namespace Notification\Pushfleet;

use Notification\Api\INotification;

class PushfleetNotification implements INotification {

	public function send($user, $message, $url) {

/*
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => "https://pushfleet.com/api/v1/send?appid=A1111111&userid=U1111111,U2222222&message=" . urlencode($message) . "&url=" . urlencode($url)
));
$resp = curl_exec($curl);
curl_close($curl);
*/

// https://pushfleet.com/api/v1/send?appid=A1111111&userid=U1111111,U2222222&message=test&url=test.com


	}

}
