## Authorization migration: XRM server (source review 2026-10-19)

This update replaces legacy, mixed access administration with the shared
authorization decision, grant administration and native-role catalog interfaces.
Entry ACL records, user and group IDs, role assignments and permission identifiers
remain processed in the same pre-existing XRM storage and authenticated sessions;
no new authorization database tables are introduced by this patch. Changes are
requested through existing authenticated microservice transport. The server may
process current actor identity, native entry IDs and direct user/group grant
metadata in memory and on the native XRM database. The Manager client forwards
calls to that authority; it does not create a new authorization store.

Write responses are only marked successful after the server confirms completion.
Manager entry-grant forms require an authenticated session and a CSRF token.
Runtime logging, historic backups, live retention periods, cross-system operator
access and legally required privacy notices depend on the deployment and cannot
be verified from the selective supplied ZIPs. Deleting native grants is supported
through the XRM service, but does not automatically erase audit logs or backups.
Operators must check their own retention, notices, authentication settings,
recipient/transfer configuration and accessibility obligations.
