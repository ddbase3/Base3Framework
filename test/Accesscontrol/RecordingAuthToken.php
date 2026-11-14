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

namespace Base3\Test\Accesscontrol;

use Base3\CoreFoundation\Token\Api\IToken;

final class RecordingAuthToken implements IToken {
	public bool $valid = false;
	public array $saves = [];
	public array $deleted = [];
	public function create($scope,$id,$size=32,$duration=3600) { return 'created-token'; }
	public function check($scope,$id,$token) { return $this->valid; }
	public function delete($scope,$id,$token) { $this->deleted[]=[$scope,$id,$token]; }
	public function clean($scope,$id) {}
	public function save($scope,$id,$token,$duration) { $this->saves[]=[$scope,$id,$token,$duration]; }
}
