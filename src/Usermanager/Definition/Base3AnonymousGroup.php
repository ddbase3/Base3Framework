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

namespace Base3\Usermanager\Definition;

use Base3\Usermanager\Api\IGroup;
use Base3\Usermanager\Criterion\Base3AnonymousCriterion;
use Base3\Usermanager\Group;

/** Built-in logical group for requests without a trusted authenticated actor. */
final class Base3AnonymousGroup implements IGroup {

	public static function getName(): string {
		return 'base3anonymousgroup';
	}

	public function getGroup(): Group {
		return Group::fromArray(['name' => 'anonymous', 'info' => '', 'archive' => 0]);
	}

	public function getCriteria(): array {
		return ['criterion' => Base3AnonymousCriterion::getName(), 'config' => []];
	}

	public function isEditable(): bool {
		return false;
	}
}
