<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IRequest;
use Base3\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTestDouble3 extends Request  {
                        public function isCli(): bool {
                                return false;
                        }
                }
