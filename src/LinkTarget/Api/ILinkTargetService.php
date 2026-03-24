<?php declare(strict_types=1);

namespace Base3\LinkTarget\Api;

/**
 * Interface ILinkTargetService
 *
 * Generates a link string for a framework-specific target definition.
 *
 * The target data is intentionally passed as a plain array instead of a
 * strongly typed value object. Different host systems may require different
 * target keys and structures. The concrete implementation is responsible for
 * interpreting the given target data and converting it into the correct link
 * format.
 *
 * Typical BASE3 target keys are:
 * - name: output name / target name
 * - out: output format
 *
 * Additional parameters are passed separately and are typically appended as
 * query parameters.
 *
 * Example:
 *
 * $service->getLink(
 * 	[
 * 		'name' => 'imprint',
 * 		'out' => 'html'
 * 	],
 * 	[
 * 		'a' => 1
 * 	]
 * );
 *
 * Implementations may define their own defaults. In the BASE3 standard
 * implementations, the "out" value defaults to "php" if it is omitted.
 */
interface ILinkTargetService {

	/**
	 * Builds a link string for the given target and additional parameters.
	 *
	 * @param array<string,mixed> $target Framework-specific target definition
	 * @param array<string,mixed> $params Additional query parameters
	 * @return string Generated link
	 */
	public function getLink(array $target, array $params = []): string;

}
