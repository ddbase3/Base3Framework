<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IConnector
 *
 * Generic abstraction for any component that connects or bridges
 * to another system, process, or implementation.
 *
 * A connector acts as the communication or integration layer that
 * provides access to data or behavior hosted elsewhere — whether
 * locally, remotely, or through another abstraction.
 *
 * Design goals:
 * - Framework-neutral: no assumptions about transport or protocol.
 * - Marker-level abstraction: defines a role, not a specific API.
 * - Complements IProxy — a Proxy delegates, a Connector connects.
 *
 * Typical usages:
 * - Remote connectors (HTTP, microservice, RPC, CLI).
 * - Data connectors (database, API, file system).
 * - In-process connectors (bridging to another module or context).
 *
 * Implementations may define their own specific methods,
 * such as `getUrl()` or `executeRequest()`, depending on context.
 *
 * @see IProxy
 */
interface IConnector {
}

