<?php declare(strict_types=1);

namespace Base3\Api;

/**
 * Interface IBootstrap
 *
 * Defines a component that executes startup or initialization logic.
 */
interface IBootstrap {

	/**
	 * Runs the bootstrap logic.
	 *
	 * This method is called during application startup to perform initialization tasks,
	 * such as registering services, loading configuration, or setting up environment state.
	 *
	 * @return void
	 */
	public function run(): void;

}

