# XRM deployment: source-verified privacy inventory

This is a technical processing inventory for the BASE3 XRM source composition
reviewed on 2026-10-26. It is not a privacy notice, deployment assessment or
claim of GDPR/ePrivacy compliance. Plugin-owned `PRIVACY.md` files contain
more specific information and must be considered together with this file.

## Processing, purpose and data categories

- XRM and Memora manage users, native groups, entry access grants, resource
  metadata and the entries and attached files that the operator stores.
- KeyHarbor manages API credentials, credential issuers/owners, service grants,
  secret verification material, expiration and revocation state and replay
  markers. Credentials must be handled as secrets.
- MissionBay stores AI usage measurements and, if enabled, conversations and
  messages. The current migration `Migration001AnonymizeAiUsage` resets stored
  AI-usage user IDs to zero and removes the `user_login` column. The
  conversation migration adds explicit owner identity and versioned
  encryption metadata without claiming that legacy records are already
  encrypted.
- The framework database migration subsystem stores technical audit metadata
  (`provider`, `migration`, `version`, `checksum`, `applied_at`, `execution_ms`)
  in `base3_migrations` to prevent untracked re-execution of schema changes.

## Storage, deletion and retention

Data are stored through the configured XRM database and any other active
backend configured by the host. Framework migration history and KeyHarbor
credential/grant state reside in the XRM database. A migration is a forward
schema change and does not delete backups, old exports or external logs.
Credential revocation affects active credentials but does not erase copies in
backups. No exact production retention or backup expiry schedule can be
verified from the available source archives.

## Access controls and security

Runtime authorisation is decided by `IAuthorizationService`; Memora owns native
entry ACL filtering. KeyHarbor credentials and service method grants do not
by themselves grant unrestricted Memora access. The database runner uses
MySQL advisory locking and migration checksums. Host-level protection of
configuration, credential material, database backups, HTTPS, administrator
access and log retention must be verified in the deployment.

## Recipients, transfers and known gaps

The source composition contains BASE3 internal microservice interfaces and
optional external integrations. Actual recipients, processors, data residency,
international transfers, provider contracts, legal bases, encryption at rest,
retention rules and incident/deletion workflows cannot be determined from
source files alone. The operator must document these for the deployed XRM
system and review plugin `PRIVACY.md` files and connected services.

## Rights and accessibility

Data access, rectification, erasure, restriction and export workflows depend
on actual deployed services, native authorities and operational policy; these
are not established by this source-level inventory. Any public-facing
administration interfaces require a separate accessibility assessment against
WCAG 2.2 AA and the applicable EU and national requirements.
