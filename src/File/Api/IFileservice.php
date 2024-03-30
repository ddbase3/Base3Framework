<?php

namespace File\Api;

interface IFileservice {

	// $base64 nicht nötig, wenn BINARYSTREAM Microservice genutzt wird
	public function getContents($filename, $base64 = false);

}
