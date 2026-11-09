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

namespace Base3\Database\Base;

use Base3\Database\Api\IDatabase;
use LogicException;

/**
 * Interface-complete base for database backends.
 *
 * This class does not own a connection. All current concrete backends override
 * every interface method. Unsupported mandatory operations fail explicitly;
 * harmless inspection methods describe the initial disconnected state.
 * New interface methods may be added here without forcing immediate changes
 * to subclasses, provided their defaults satisfy the contract.
 */
abstract class AbstractDatabase implements IDatabase {

	/** @inheritDoc */
	public function connect(): void {
		throw new LogicException(static::class . ' does not implement connect().');
	}

	/** @inheritDoc */
	public function connected(): bool {
		return false;
	}

	/** @inheritDoc */
	public function disconnect(): void {
		// No connection is owned by this base.
	}

	/** @inheritDoc */
	public function beginTransaction(): void {
		throw new LogicException(static::class . ' does not implement beginTransaction().');
	}

	/** @inheritDoc */
	public function commit(): void {
		throw new LogicException(static::class . ' does not implement commit().');
	}

	/** @inheritDoc */
	public function rollback(): void {
		throw new LogicException(static::class . ' does not implement rollback().');
	}

	/** @inheritDoc */
	public function nonQuery(string $query): void {
		throw new LogicException(static::class . ' does not implement nonQuery().');
	}

	/** @inheritDoc */
	public function scalarQuery(string $query): mixed {
		throw new LogicException(static::class . ' does not implement scalarQuery().');
	}

	/** @inheritDoc */
	public function singleQuery(string $query): ?array {
		throw new LogicException(static::class . ' does not implement singleQuery().');
	}

	/** @inheritDoc */
	public function &listQuery(string $query): array {
		throw new LogicException(static::class . ' does not implement listQuery().');
	}

	/** @inheritDoc */
	public function &multiQuery(string $query): array {
		throw new LogicException(static::class . ' does not implement multiQuery().');
	}

	/** @inheritDoc */
	public function affectedRows(): int {
		return 0;
	}

	/** @inheritDoc */
	public function insertId(): int|string {
		return 0;
	}

	/**
	 * Escaping requires backend-specific rules and must never be guessed.
	 *
	 * @param string $str Unescaped input.
	 * @return string Escaped SQL fragment if supported.
	 * @throws LogicException Always, unless a backend overrides this method.
	 */
	public function escape(string $str): string {
		throw new LogicException(static::class . ' does not implement escape().');
	}

	/** @inheritDoc */
	public function isError(): bool {
		return false;
	}

	/** @inheritDoc */
	public function errorNumber(): int {
		return 0;
	}

	/** @inheritDoc */
	public function errorMessage(): string {
		return '';
	}
}
