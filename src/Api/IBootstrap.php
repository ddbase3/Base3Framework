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

