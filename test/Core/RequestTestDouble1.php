<?php declare(strict_types=1);

namespace Base3Test\Core;

use Base3\Api\IRequest;
use Base3\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTestDouble1 implements \ArrayAccess, \IteratorAggregate  {

                        private array $data = ['a' => 1, 'b' => 2];

                        public function offsetExists($offset): bool {
                                return array_key_exists($offset, $this->data);
                        }

                        public function offsetGet($offset): mixed {
                                return $this->data[$offset] ?? null;
                        }

                        public function offsetSet($offset, $value): void {
                                $this->data[$offset] = $value;
                        }

                        public function offsetUnset($offset): void {
                                unset($this->data[$offset]);
                        }

                        public function getIterator(): \Traversable {
                                return new \ArrayIterator($this->data);
                        }
                }
