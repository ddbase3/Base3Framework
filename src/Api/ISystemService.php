<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Provides information about the runtime environment in which BASE3 is executed.
 *
 * BASE3 can operate in two modes:
 *
 * 1) Standalone
 *    - BASE3 is the host system.
 *    - Host and embedded system are identical.
 *
 * 2) Embedded
 *    - BASE3 runs inside another host system (e.g. a platform or application).
 *    - Host and embedded system may differ in name and/or version.
 *
 * This service exposes system and version metadata so implementations can
 * react to environment differences (e.g. compatibility handling, feature switches).
 *
 * Terminology:
 * - Host system: The outer system or platform providing the runtime.
 * - Embedded system: The BASE3 system (or BASE3-based application) running inside the host.
 *
 * Version values:
 * - Versions are returned as human-readable strings.
 * - No comparison semantics are implied at this level.
 * - If a value is unknown, an empty string ("") MUST be returned.
 */
interface ISystemService {

	/**
	 * Returns the name of the host system in which BASE3 is running.
	 *
	 * Examples:
	 * - "BASE3" (standalone)
	 * - "ILIAS" (embedded)
	 *
	 * @return string Host system name or "" if unknown.
	 */
	public function getHostSystemName() : string;

	/**
	 * Returns the version of the host system in which BASE3 is running.
	 *
	 * @return string Host system version or "" if unknown.
	 */
	public function getHostSystemVersion() : string;

	/**
	 * Returns the name of the embedded system (the BASE3 system itself).
	 *
	 * In standalone mode this is typically identical to the host system name.
	 *
	 * @return string Embedded system name or "" if unknown.
	 */
	public function getEmbeddedSystemName() : string;

	/**
	 * Returns the version of the embedded system (the BASE3 system itself).
	 *
	 * In standalone mode this is typically identical to the host system version.
	 *
	 * @return string Embedded system version or "" if unknown.
	 */
	public function getEmbeddedSystemVersion() : string;
}
