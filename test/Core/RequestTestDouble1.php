<?php declare(strict_types=1);

/***********************************************************************
 * This file is part of BASE3 Framework.
 *
 * BASE3 Framework is a lightweight, modular PHP framework for scalable
 * and maintainable web applications. Built for extensibility,
 * performance, and modern development, it can run standalone or
 * integrate as a subsystem within a host system.
 *
 * Developed by Daniel Dahme
 * Licensed under GPL-3.0
 * https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * https://base3.de
 * https://github.com/ddbase3/Base3Framework
 **********************************************************************/

namespace Base3\Test\Core;

use Base3\CoreFoundation\Api\IRequest;
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
