<?php declare(strict_types=1);

namespace Base3\Microservice\Api;

use Base3\Api\IOutput;

/**
 * Interface IMicroservice
 *
 * Marker interface for microservice endpoints that provide output.
 * Inherits all methods from IOutput.
 */
interface IMicroservice extends IOutput {}

