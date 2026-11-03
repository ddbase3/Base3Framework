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

namespace Base3\Usermanager\Api;

use Base3\Api\IBase;
use Base3\Usermanager\Group;

/** Discoverable code-owned BASE3 group definition, distinct from native host roles and grants. */
interface IGroup extends IBase {

	/**
	 * Returns a stable logical group name. Duplicate names across discovered definitions are invalid.
	 * The technical IBase::getName() remains the lowercase unnamespaced implementation class name.
	 *
	 * @return Group Group identity, name and human-readable description; no database ID required
	 */
	public function getGroup(): Group;

	/**
	 * Returns the fixed code-owned group membership expression. A criterion leaf is
	 * ['criterion' => getName(), 'config' => [...]], compositions are ['any' => [...]],
	 * ['all' => [...]] or ['not' => expression]. An empty array denies membership by default.
	 * The expression is evaluated using IUserCriterion, not as an RBAC permission grant.
	 * For editable groups, the host may add separately configured members with a logical OR,
	 * but must never replace or delete code-owned membership. This enables deployment-specific
	 * recovery identities without a privilege bypass or a separate group registry.
	 *
	 * @return array<string,mixed> Declarative criterion expression, with no runtime side effects
	 */
	public function getCriteria(): array;

	/**
	 * Indicates whether host-managed membership criteria may be added to this group.
	 * Code-owned criteria remain effective even if administrators save an empty expression;
	 * locked groups do not accept any host-managed additional membership.
	 *
	 * @return bool True when administrative changes to criteria are supported
	 */
	public function isEditable(): bool;
}
