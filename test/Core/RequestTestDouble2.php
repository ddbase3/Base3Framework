<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IRequest;
use Base3\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTestDouble2 extends Request  {
                        protected function parseCliArgs(): void {
                                parent::parseCliArgs();
                        }
                        public function isCli(): bool {
                                return true;
                        }
                }
