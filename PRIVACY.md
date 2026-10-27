# Privacy and data handling (source-verified technical inventory)

This file records what can be established from the Contourz source code supplied
for the current migration. It is not a replacement for the public privacy notice
or a deployment-level GDPR assessment.

## Processing and purpose

- `ContourzPubJob` reads data from XRM through `IEntityDataService` and exports
  selected public information into local JSON files.
- The publishing job removes the contact fields `phone`, `phonework`, `mobile`,
  `email1`, `email2`, `email3`, `fax`, and `dateofbirth` from the exported
  contact payload. Address latitude/longitude values are rounded in the main
  entity projection.
- `LogVisitMiddleware` records an origin parameter and IP address in a local
  `visit` database table when the `o` request parameter is supplied.
- The new comparison output reads the same source entities, compares the public
  projection to local exports, and prints counts, public identifiers and changed
  top-level field names. It does not print field values or persist reports.

## Storage and recipients

- Public entity exports are written to `local/Contourz/`; location map exports
  are written to `local/ContourzLocations/`.
- Image data and publication lists are stored locally under
  `local/ContourzPhotos/` by the existing publishing/sync workflows.
- XRM is accessed using configured BASE3 microservice connections. The physical
  hosting locations, processors and international transfer arrangements cannot
  be established from these source archives alone.

## Retention, deletion and security

- Source code establishes no universal retention period for exports, visit
  records, server logs or backups. These must be documented and verified for the
  deployment.
- The existing image sync performs deletion of local files in some cases; it
  will be reviewed before the image migration. This comparison patch performs
  no deletion and changes no production data files.
- Microservice authentication, HTTPS configuration, network restrictions,
  hosting access controls and backup protection require deployment verification.

## Known gaps and follow-up

- Verify whether any other sensitive contact fields require exclusion before
  moving the public site to direct entity access. The existing export field
  filter is not proof that an unrestricted entity response is safe to expose.
- Verify live host retention/deletion policies and data-subject workflows.
- Verify the existing published privacy notice, processors, server logs and
  international transfers against the actual production deployment.
- Verify the full public-data authorization boundary before enabling the new
  live data read path for frontend requests.

## Migration step 2 (read-only preparation)

- `ContourzPubJob::getPublishedEntry()` can fetch a single Memora entity by its
  binary UUID through the existing remote `IEntityDataService` and only emits
  data after matching the existing publication tag/type/exclusion criteria.
- `ContourzDirectDataProbeOutput` is CLI-only and returns the UUID, whether an
  entry is public, its type and query time, without writing or displaying payload
  contents. The HTTP pages continue reading the existing JSON exports.

## Migration step 2: live public reads

- The public header, location map and search now retrieve Memora entities via
  the authenticated XRM `IEntityDataService` microservice; data is projected
  through the existing publication rules before rendering.
- Linked items are fetched in one structured UUID query and filtered for public
  visibility. The image serving workflow and local JSON publishing job remain
  active. No new personal data is intentionally persisted by the live readers.
- The remote service returns entity fields to the Contourz PHP process before
  the publication projection removes excluded fields. Audit the XRM service
  authorization and transport as part of production signoff. Hosting, deletion
  periods, backup retention, processors and GDPR compliance remain unverified.

## Service-to-service identity for public projections

- The serialized XRM EntityData/EntityFile connector on Contourz uses the existing `INTERNALONLY` flag, avoiding propagation of the current website visitor identifier in the `auth` header. The remote request still uses the configured service credential.
- This is not a relaxation of the publication rules. Contourz must enforce the existing published type, tag and exclusion filters before rendering. Raw remote entity results are not intentionally exposed to visitors.
- Verify the configured XRM service credential, server-side permissions, and data minimization in deployment; no conclusion about GDPR compliance is inferred.
