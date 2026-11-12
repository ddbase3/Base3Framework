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

namespace Base3\Migration\Api;

/**
 * Marks an existing database migration whose stored file checksum is temporarily
 * exempt from comparison during incremental migration-contract preparation.
 *
 * This marker does not inherit IDatabaseMigration and does not change the
 * migration's identity, ordering, execution, history or required methods.
 * Only already-applied marked migrations skip the checksum comparison.
 * New executions still record the concrete source checksum in the ordinary
 * base3_migrations history. The marker does not provide rollback capability.
 *
 * Remove this marker only after the applied history's checksum has been
 * reconciled with the final marker-free source file in every affected database.
 */
interface ILegacyDatabaseMigration {
}
