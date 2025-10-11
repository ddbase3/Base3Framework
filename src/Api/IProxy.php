<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Structural interface for delegating wrappers (proxies).
 * No transport or location semantics implied.
 */
interface IProxy {

	/**
	 * Returns the delegated/underlying instance used by this proxy.
	 *
	 * This accessor exists for diagnostics, composition, and framework tooling.
	 * Callers should not rely on the concrete type beyond the public contract
	 * they already use; prefer interacting through the domain interface.
	 *
	 * @return object The exact object that receives delegated calls.
	 */
	public function getProxiedInstance(): object;
}

