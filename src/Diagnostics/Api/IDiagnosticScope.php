<?php declare(strict_types=1);

namespace Base3\Diagnostics\Api;

use Base3\Api\IBase;
use Base3\Api\ISortable;

/**
 * Describes one classmap-discoverable diagnostic area and its UI display.
 *
 * The section named getName() in the providing module's DiagnosticScopes
 * translations supplies title and description. The diagnostic runner does
 * not depend on the selected UI implementation.
 */
interface IDiagnosticScope extends IBase, ISortable {

	/**
	 * Returns the IDisplay::getName() responsible for rendering this scope.
	 *
	 * The name is a technical classmap identifier, not a translated label.
	 * The host UI must skip scopes whose selected display is not installed.
	 *
	 * @return string Non-empty discoverable IDisplay name
	 */
	public function getDisplayName(): string;

}
