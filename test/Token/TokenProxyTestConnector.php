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

namespace Base3\Test\Token;

final class TokenProxyTestConnector {

	public array $calls = [];

	public function create($scope, $id, $size = 32, $duration = 3600): string {
		$this->calls[] = ['create',$scope,$id,$size,$duration];
		return 'token';
	}
	public function check($scope, $id, $token): bool {
		$this->calls[] = ['check',$scope,$id,$token];
		return $token === 'token';
	}
	public function delete($scope, $id, $token): void { $this->calls[] = ['delete',$scope,$id,$token]; }
	public function clean($scope, $id): void { $this->calls[] = ['clean',$scope,$id]; }
}
