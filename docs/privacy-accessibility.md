# BASE3 Privacy, Data Protection, and Accessibility Requirements

> **Binding engineering standard.** These requirements are mandatory for BASE3 framework changes, foundation plugins, reusable plugins, project integrations, user interfaces, APIs, jobs, background processing, and documentation. A change that violates an applicable requirement MUST NOT be considered complete or release-ready.
>
> **Legal review date:** 2026-10-14. Review the cited official sources for amendments, national implementations, harmonised-standard references, and deployment-specific duties before each materially affected release.
>
> **Important distinction:** EU law applies according to its specific scope and to the role of the controller, processor, manufacturer, or service provider. BASE3 deliberately adopts stronger internal engineering requirements for its own work even when an individual deployment is outside a particular legal obligation. A repository document cannot replace a legally required deployment-specific privacy notice, accessibility statement, contract, or legal assessment.

## 1. Purpose and authority

This document establishes one consistent engineering policy for privacy and accessibility.
It applies alongside `overview.md`, `architecture-principles.md`, `coding-conventions.md`, `foundation-plugins.md`, and the relevant subsystem documentation.
Its requirements MUST be implemented at the existing architectural owner, not through duplicated services, workaround adapters, or compensating parallel structures.
A feature is NOT finished merely because the PHP path produces the requested output.
Its data life cycle, deletion behavior, accessible usage, tests, documentation, and deployment dependencies are part of the same implementation.

The normative words **MUST**, **MUST NOT**, **REQUIRED**, **SHOULD**, and **MAY** have their ordinary engineering-policy meanings here.
A MUST is an internal BASE3 requirement, unless explicitly qualified as a statutory duty in a defined legal scope.
A legal exemption does not silently waive BASE3's internal accessibility or privacy standard.
A waiver of a BASE3-specific rule requires explicit project-owner approval and documented risk; statutory duties cannot be waived this way.
Unknown behavior MUST be recorded as unknown; it MUST NOT be described as secure, compliant, deleted, or unavailable without evidence.

## 2. Legal framework and precise scope

### 2.1 GDPR: Regulation (EU) 2016/679

The General Data Protection Regulation (GDPR, German: DSGVO) governs personal-data processing in its territorial and material scope.
Its primary implementing entity is a controller; processors have their own direct duties and contractual duties.
A framework does not, by itself, determine the legal controller, legal basis, or retention period of every installed application.
Implementations MUST expose controls enabling lawful processing by the responsible deployment.
Implementations MUST NOT claim GDPR compliance merely because an interface, privacy notice, or `PRIVACY.md` exists.

Core rules include:

- Article 5: lawfulness, fairness, transparency, purpose limitation, data minimisation, accuracy, storage limitation, integrity/confidentiality, accountability.
- Article 6: a valid legal basis is required for each relevant purpose.
- Articles 7 and 8: consent conditions, withdrawal, and applicable conditions for children's consent to information-society services.
- Article 9: additional restrictions and exceptions for special categories of personal data.
- Articles 12 to 22: information and data-subject rights, including access, rectification, erasure, restriction, portability, objection, and automated-decision safeguards where applicable.
- Articles 24 and 25: controller accountability, data protection by design and by default.
- Article 28: processor arrangements and required contract content where applicable.
- Article 30: records of processing activities where required.
- Article 32: risk-appropriate technical and organisational security.
- Articles 33 and 34: personal-data breach notification and communication where thresholds apply.
- Article 35: data protection impact assessment (DPIA) where processing is likely to result in high risk.
- Articles 37 to 39: data protection officer arrangements where applicable.
- Articles 44 to 49: international transfers and the applicable transfer mechanisms.

Official text: <https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32016R0679>.
EDPB Article 25 guidance: <https://www.edpb.europa.eu/documents/guideline/guidelines-42019-on-article-25-data-protection-by-design-and-by-default_en>.

### 2.2 ePrivacy: Directive 2002/58/EC

The ePrivacy Directive applies to its regulated electronic-communications and terminal-access contexts and is implemented through national law.
Article 5(3), as amended, is relevant to storing information on or accessing information from terminal equipment.
Where the national implementation requires consent, the application MUST obtain valid consent before non-exempt storage/access.
Exemptions for technically necessary operations MUST be assessed narrowly and documented.
The presence or absence of personal data is not the sole test for terminal-equipment rules.
Consent for terminal access and the GDPR legal basis for subsequent personal-data processing are distinct assessments.

Official text: <https://eur-lex.europa.eu/eli/dir/2002/58/oj>.
German implementation example: TDDDG Section 25, <https://www.gesetze-im-internet.de/ttdsg/__25.html>.

### 2.3 Public-sector accessibility: Directive (EU) 2016/2102

Directive (EU) 2016/2102 addresses websites and mobile applications of public-sector bodies within its scope.
Article 4 requires accessibility against the perceivable, operable, understandable, and robust principles.
Article 7 covers accessibility statements and feedback; Article 8 covers monitoring/reporting; Article 9 covers enforcement, subject to national implementation.
Public-sector deployments MUST assess their national implementing legislation and any additional public-sector or education-specific obligations.
A reusable BASE3 plugin MUST NOT assume that host accessibility duties disappear when the plugin is embedded.

Official text: <https://eur-lex.europa.eu/eli/dir/2016/2102/oj>.

### 2.4 European Accessibility Act: Directive (EU) 2019/882

The European Accessibility Act (EAA) covers specified products and services, not every website or every internal business application.
Its scope includes, among others, certain consumer banking services, e-commerce services, e-books, electronic communications, and specified transport-related services.
The rules for covered products placed on the market and covered consumer services generally apply from 28 June 2025, subject to the Directive's transitions and national implementation.
Article 4 and Annex I describe accessibility requirements for covered products and services.
Article 14 governs documented assessments of fundamental alteration or disproportionate burden where invoked.
Article 4(5) exempts qualifying microenterprises providing services from specified EAA accessibility requirements; the exemption MUST NOT be projected onto unrelated legal duties or onto BASE3's internal policy.
Article 32 provides certain transitional arrangements.
A developer MUST determine whether the actual operator, offering, and users bring a service within scope; a mere use of PHP or BASE3 is not the test.

Official text: <https://eur-lex.europa.eu/eli/dir/2019/882/oj>.

### 2.5 Technical standard: EN 301 549

EN 301 549 specifies accessibility requirements for ICT products and services, including websites, software, and documents.
EN 301 549 v3.2.1 (2021) was cited for the EU Web Accessibility Directive through Commission Implementing Decision (EU) 2021/1339.
EN 301 549 v4.1.1 was published in September 2026 and references WCAG 2.2.
As reported by AccessibleEU on 7 September 2026, v4.1.1 was not yet cited in the EU Official Journal as a replacement legal reference for EAA/WAD conformity presumption.
**Developers MUST distinguish the latest published standard from the version legally harmonised for a specific regime.**
The harmonisation status MUST be checked again when preparing a compliance declaration or major release.
BASE3's own UI quality baseline is WCAG **2.2 AA**, independent of a temporarily older legally harmonised reference.

Official decision: <https://eur-lex.europa.eu/eli/dec_impl/2021/1339/oj>.
September 2026 status: <https://accessible-eu-centre.ec.europa.eu/content-corner/news/european-accessibility-standard-en-301-549-has-been-updated-2026-09-07_en>.

### 2.6 W3C WCAG 2.2

WCAG 2.2 is a W3C Recommendation and provides testable accessibility success criteria.
Its principles are perceivable, operable, understandable, and robust (POUR).
BASE3 MUST satisfy every applicable level A and AA criterion for new and changed web interfaces; the scope and exceptions MUST be documented.
Compliance MUST be checked against complete user journeys, not just individual components.
WCAG compliance is not automatically full compliance with all legal requirements or the entire EN 301 549 standard.
Higher accessibility practices SHOULD be used when they clearly improve inclusion.

Standard: <https://www.w3.org/TR/WCAG22/>.
Guidance: <https://www.w3.org/WAI/standards-guidelines/wcag/>.

### 2.7 National law and German examples

EU directives require national transposition; implementation, market supervision, procedures, and additional duties can vary between Member States.
For deployments in Germany, assess at least the following where applicable:

- `BFSG` and `BFSGV`, implementing EAA requirements for covered products/services, effective principally since 28 June 2025.
- `BGG`, particularly Sections 12a to 12c for covered federal public bodies.
- `BITV 2.0`, including accessibility statements and applicable federal requirements; state legislation may apply to state entities.
- `TDDDG` Section 25 for access to information on terminal equipment.
- `BDSG` and any sector-specific national data-protection law where applicable alongside GDPR.

References:
<https://www.gesetze-im-internet.de/bfsg/>,
<https://www.gesetze-im-internet.de/bfsgv/>,
<https://www.gesetze-im-internet.de/bgg/>,
<https://www.gesetze-im-internet.de/bitv_2_0/>,
<https://www.gesetze-im-internet.de/ttdsg/>.

### 2.8 Other legislation must be evaluated by feature

Sector-specific obligations can attach to health, employment, education, payments, public procurement, telecommunications, media, or AI-based decision-making.
A project MUST assess any additional laws triggered by its real use case; they are not automatically applicable to all BASE3 plugins.
Do not use an open-ended list of legislation as a substitute for a written scope decision.
The relevant owner MUST maintain a dated applicability record with links to authoritative sources.
Proposed legislation MUST NOT be described as an enacted or applicable obligation.

## 3. Non-negotiable BASE3 requirements

### 3.1 No undocumented personal-data behavior

Every data-producing or data-consuming feature MUST identify what it processes and why.
Every persistent dataset MUST have an owner, documented storage location, retention rule, deletion route, and access boundary.
Personal data MUST NOT be stored indefinitely merely because no cleanup requirement was implemented.
Optional modules MUST disclose processing that occurs only when enabled.
An unknown retention period is a release blocker for newly introduced personal-data persistence.

### 3.2 No inaccessible user interface

Any BASE3-provided interactive interface MUST be designed for keyboard, screen-reader, zoom, high-contrast, and other assistive-technology use.
A visual interface that works only with a mouse is non-conforming.
An AJAX interface without valid focus management and status announcements is non-conforming.
Charts that communicate indispensable information by colour alone are non-conforming.
Accessibility fixes belong in the owning component/template, not as a second shadow UI.

### 3.3 No isolated implementation-only completion

A code change MUST include the relevant privacy inventory, deletion lifecycle, UI accessibility checks, automated/manual tests, README links, and affected documentation in the same patch.
Interfaces whose implementations process or erase data MUST document semantics and edge cases in PHPDoc.
No developer should need a second prompt to learn whether a feature retains prompts, stores identifiers, or supports deletion.
Unchanged unrelated documentation SHOULD remain unchanged.

### 3.4 No compensating architecture

Do not invent privacy coordinators, ad hoc service registries, shadow state, duplicate repositories, or accessibility overlay frameworks to avoid fixing an existing owner.
Use BASE3 container services for known dependencies and class-map discovery for truly discoverable components.
Use `SettingsStore`, `StateStore`, `Configuration`, and `ConfigValueResolver` according to their established roles.
The privacy requirement does not itself justify introducing a new foundation contract.
On overlap, propose reuse or refactoring and obtain architectural approval before creating another structure.

### 3.5 No unverifiable claim

Do not write "GDPR compliant", "fully accessible", "EU-only", "anonymised", or "deleted everywhere" without evidence covering the actual configured runtime.
Do not infer provider retention, logging, training use, region, or subprocessors from an API adapter's class name.
Do not confuse a hash with anonymisation.
Do not confuse logical deletion with physical erasure.
Do not confuse an accessibility scanner's clean report with full human accessibility.

## 4. Required repository documentation

### 4.1 Repository-root `PRIVACY.md` is mandatory

Every BASE3-related repository MUST contain a maintained `PRIVACY.md` in its repository root.
This includes the framework and repositories that introduce independent data processing, storage, provider calls, or host integration behavior.
It MUST document current technical processing behavior as verified from source and configuration, not aspirational behavior.
It MUST be updated in the same change that alters any documented storage, transfer, processing, retention, audit, security, deletion, or user-rights behavior.
It MUST name the source revision or review date used for the inventory.
It MUST flag unknown deployment-dependent facts and require operator completion before production use.
An existing `PRIVACY.md` MUST be extended, not silently replaced by an unrelated template.

### 4.2 Mandatory `PRIVACY.md` sections

The root document MUST include at least:

1. Scope, component/version/source snapshot, and limitations.
2. Roles and boundary: framework, host, operator, processor, external providers.
3. Purpose and personal-data categories, including inferred and derived identifiers.
4. Collection and entry points; network and process data flows.
5. Source-backed list of databases, tables, indexes, caches, files, sessions, state stores, queues, logs, object stores, vector stores, and backups.
6. Persistent data fields and sensitivity; whether full content is stored.
7. User/tenant ownership and authorisation boundaries.
8. Categories of recipients, processors, subprocessors, and external endpoints.
9. Connection configuration, secrets, credential handling, and provider-side behavior where known.
10. Retention criteria, cleanup implementation, execution trigger, and failures.
11. Deletion, correction, export, restriction, and propagation into copies and derived data.
12. Backup restoration, expiration, and re-deletion guarantees or explicit limitations.
13. Logging, monitoring, observability, metrics, and audit data.
14. International transfers and the dependency on deployment contracts/configuration.
15. Security controls and material known limitations.
16. Data-subject request capabilities and responsibility mapping.
17. Default-on, opt-in, and configuration-sensitive processing.
18. Known gaps, deployment checklist, and verification status.
19. Links to relevant code owners, migrations, tests, contracts, and other documentation.
20. Change history and review information.

### 4.3 Distinguish technical inventory from legal privacy notice

`PRIVACY.md` documents source-observable technical facts and deployment dependencies.
The operator's legally required privacy information under GDPR Articles 12 to 14 is separate.
The repository MUST NOT falsely name a universal controller or processor for all BASE3 deployments.
Where the repo itself hosts a real service, operator-specific legal facts MUST be documented or linked accurately.
The absence of a controller-specific legal basis MUST be labelled as a deployment requirement, not silently filled with consent.

### 4.4 Documentation change trigger

Update privacy documentation whenever a change modifies:

- personal-data categories, identity fields, or user/tenant linkage;
- persistent schemas, indexes, files, caches, session contents, or queue payloads;
- retention periods, cleanup rules, deletion paths, or restoration behavior;
- logs, tracing, metrics, event payloads, diagnostics, or administrative visibility;
- provider endpoints, recipients, subprocessors, international flows, or model context;
- collection scope, access permissions, consent choices, exports, or user rights;
- newly enabled background jobs, imports, retrieval indexes, or derived datasets.

### 4.5 Documentation quality gate

A reviewer MUST be able to answer from documentation: what, why, where, who, how long, how deleted, to whom transmitted, and what remains after deletion.
If any answer requires guessing, the affected feature is not adequately documented.
A changed source path and unchanged documentation MUST be justified by an explicit no-impact review.

## 5. Data inventory and ownership

### 5.1 Inventory by real runtime composition

BASE3 can run standalone or embedded in ILIAS, Moodle, WordPress, or another host.
The final processing surface depends on the configured project plugins, implementations, and host services.
Maintain a runtime-specific inventory; do not list every discoverable class as active processing.
List inactive capabilities separately if their code exposes a foreseeable optional boundary.

### 5.2 Required inventory columns

For each dataset or processing action record:

| Field | Required meaning |
| --- | --- |
| Owner | Plugin/backend/module that owns the data or operation |
| Trigger | User action, request, job, hook, event, import, or scheduled run |
| Data | Fields, classes, payload shape, personal/sensitive potential |
| Purpose | Why each relevant field must be processed |
| Legal assessment | Controller's legal basis and Article 9 condition if applicable |
| Storage | Exact table/path/key prefix/provider and backend dependence |
| Subjects | Users, visitors, administrators, third parties, employees, etc. |
| Access | Reader/writer roles, row-level/tenant permissions, recipients |
| Retention | Period or objective criteria, start event, cleanup owner |
| Deletion | Trigger, operation, derived copies, exceptions, verification |
| Export | Available data-subject retrieval or operator interface |
| Transfers | Network endpoints, geography, processors, subprocessor chain |
| Evidence | Source paths, tests, configuration, review date |

### 5.3 Data categories that require review

Check direct identifiers such as username, email address, IP address, account ID, session ID, and device identifier.
Check indirect identifiers such as stable pseudonyms, hashed IDs, correlation IDs, resource ownership, and request context.
Check user-generated text, attachments, uploaded files, audio, speech transcript, images, comments, feedback, and conversation history.
Check telemetry, audit events, exceptions, usage billing, provider request IDs, and admin activity.
Check inferred characteristics, embeddings, vectors, search terms, categories, preferences, and model outputs.
Check special-category information that can be present in otherwise generic text fields.

### 5.4 Persistence can be indirect

A service may retain data through an injected database, session, settings store, state store, cache, filesystem, logger, queue, or remote provider.
The declaring interface does not reveal the concrete persistence backend.
Document actual runtime compositions and known possible backends.
A feature that does not persist data directly can still cause logging or remote provider retention.

### 5.5 Source and derived data

Map source records to derived representations such as indexes, embeddings, extracted text, summaries, thumbnails, exports, and audit records.
Keep enough provenance to identify derived copies for correction, deletion, and access revocation.
Do not store a second full copy of the source merely to enable this mapping.
Where tracking a derived artifact requires a new structure, review existing lifecycle and resource ownership first.

## 6. Lawful processing and data minimisation

### 6.1 Purpose before implementation

Each operation involving personal data MUST have a defined, specific purpose.
The controller MUST determine the relevant Article 6 legal basis before enabling that purpose.
Developers MUST NOT select consent as a generic fallback where another basis or no lawful basis is actually appropriate.
Purpose changes must trigger a renewed legal and architectural review.

### 6.2 Mandatory minimisation

Collect only fields necessary for the declared purpose.
Do not select `SELECT *` from sensitive stores when a smaller field projection suffices.
Do not propagate complete records through events or DTOs if only identifiers are needed.
Do not forward internal access-control metadata to clients or models without necessity.
Do not retain user-generated text in debug traces merely for convenience.

### 6.3 Sensitive and special-category data

Free text, files, speech, and AI prompts can contain Article 9 special-category data even if the form did not request it.
Do not treat a generic text column as harmless by default.
Prohibit or restrict such inputs where no lawful basis and Article 9 exception are established.
Use appropriate classification, permission, minimisation, and redaction controls.
Do not claim Article 9 processing is legal solely because a user submitted the content.

### 6.4 Children and vulnerable users

Assess age and service context where children or vulnerable people may be involved.
Article 8 GDPR has specific scope and national variations for consent to certain information-society services.
Do not hardcode a single EU-wide age threshold.
Design controls and explanations that are understandable to the relevant audience.

### 6.5 Privacy-friendly defaults

Optional telemetry, provider transfers, user profiling, persistent memory, and unnecessary tracking MUST be disabled by default unless a justified, documented baseline is required.
Where a feature requires processing for the requested service, expose the narrowest compatible default.
Account, tenant, and user scopes MUST be explicit and restrictive.
New processing MUST NOT become globally enabled simply because the class map discovers it.

## 7. Data retention and deletion

### 7.1 Retention is a functional contract

Every retained personal-data category MUST have a documented lifecycle.
Specify what starts the retention clock: collection, last use, session end, transaction completion, account deletion, or another justified event.
Specify duration or defensible objective deletion criteria.
Specify ownership of cleanup and the worker/job/manual operation that enforces it.
Indefinite retention without a valid and documented justification is prohibited.

### 7.2 Do not invent universal retention periods

GDPR does not prescribe one universal numerical retention period for all data.
Set periods per purpose, applicable legal obligation, and risk.
Do not copy retention periods from MissionBay, other plugins, or example repositories into unrelated systems.
Use short periods for unnecessary diagnostics, but justify any fixed number.

### 7.3 Deletion must reach the owning boundary

The storage or feature owner MUST implement the actual delete operation.
Do not build a duplicate delete repository that bypasses the existing ownership model.
Data-subject workflows may coordinate owners but MUST NOT duplicate storage business logic.
A successful API response requires documented semantics for what was actually removed.

### 7.4 Deletion propagation

For a deletion event, identify live primary records, associated child records, attachments, search indexes, caches, derived summaries, vector payloads, queues, and active exports.
Delete or invalidate all copies for which deletion is legally required.
Make cross-store propagation retryable and traceable without unnecessarily retaining deleted content.
If an external provider retains data, record the provider-side deletion mechanism and known limits.

### 7.5 Right to erasure and exceptions

GDPR Article 17 establishes a conditional right to erasure with legally defined exceptions.
Do not promise unconditional immediate deletion where retention is required by law or another exception applies.
When an exception applies, restrict processing as required and record a narrowly scoped reason.
The technical implementation MUST support the legally determined decision, not decide legal exemptions by guessed rules.

### 7.6 Soft deletion is not final deletion

A soft-delete flag can be an intermediate lifecycle state.
It MUST NOT be described as physical erasure.
Define if and when hard deletion occurs, including dependent rows and derived artifacts.
Exclude soft-deleted records from ordinary access immediately where appropriate.

### 7.7 Backups

Document backup scope, encryption, access controls, retention, restore procedures, and expiration.
Do not promise that a live deletion retroactively rewrites immutable backups if it does not.
Where lawful deletion is deferred until backup expiry, document the protective measures and applicable legal assessment.
Restoration processes MUST replay or re-apply outstanding deletions before a restored system is exposed to normal users.

### 7.8 Logs and audit retention

Audit records can themselves contain directly identifiable or sensitive content.
Collect only what the audit purpose requires.
Separate audit retention policy from transaction or conversation retention.
Deletion must assess whether legal obligations or security needs justify limited audit persistence.
Do not leave full prompts or arbitrary request bodies in long-lived logs by default.

### 7.9 Background cleanup

Use established BASE3 workers/jobs for periodic cleanup, not ad hoc HTTP-triggered deletion.
A job MUST have an owner, schedule/policy, idempotency expectations, observable success/failure, and tests.
A configured cleanup is not proof that cleanup actually ran; monitor execution and failed attempts.
Migration steps must not silently perform uncontrolled historical erasure.

### 7.10 Deletion idempotency

Repeated delete requests SHOULD reach the same final state without reintroducing data.
Retries MUST respect authorization and scope.
Partial failures MUST be explicit and recoverable.
Use existing state mechanisms for cursors/locks only when necessary.

### 7.11 Referential integrity

Document cascade and restrict behavior of foreign keys where relevant.
Do not rely on a cascade assumption without checking the actual schema.
Do not orphan attachments, storage objects, or vector points.
Test deletion with related objects, multiple tenants, and partial failures.

### 7.12 Retention change review

Changing a retention period affects stored historical data and may require a migration or cleanup strategy.
Update the actual implementation, technical privacy inventory, operator guidance, and tests in the same patch.
Obtain approval before shortening retention where legal holds or significant business data could be affected.

## 8. Data-subject rights and operational controls

### 8.1 Rights are deployment responsibilities with technical support

The controller determines lawful handling of rights requests under GDPR Articles 12 to 22.
BASE3 plugins MUST provide accurate ways for the operator to locate, export, rectify, restrict, or erase their owned data where necessary.
A reusable library MUST NOT pretend to be the controller of every deployment.

### 8.2 Access and export

Locate the subject's records across active backends, including associated derived data where required.
Authorise the request before revealing data.
Preserve context and understandable field meanings.
Avoid adding extra personal data to the export.
Prevent cross-user and cross-tenant disclosure.

### 8.3 Rectification

Define the authoritative owner of each field.
Correct relevant derived or indexed data, or rebuild it where necessary.
Do not patch a second denormalised copy while leaving the owner incorrect.
Preserve any legally required correction history in a limited, justified form.

### 8.4 Restriction and objection

Where the controller determines restriction or objection requirements apply, support blocking processing operations while preserving only permitted storage/handling.
Do not interpret a UI hidden state as processing restriction.
Jobs, APIs, retrieval, providers, and exports must respect the effective restriction where required.

### 8.5 Portability

Article 20 portability has specific conditions; do not promise universal portability of every dataset.
Support structured, commonly used, machine-readable formats for applicable portable data.
Avoid undocumented proprietary encodings when an interoperable format exists.

### 8.6 Response timing

GDPR Article 12 generally requires a response without undue delay and within one month; qualifying complexity/volume can justify extensions under the statutory conditions.
Operators MUST own the identity verification, logging, timing, and decision process.
Internal feature code MUST make retrieval and deletion possible within the operator's workflow.

### 8.7 Automated decisions

Assess Article 22 where solely automated decisions produce legal or similarly significant effects.
Do not assume ordinary text generation always constitutes such a decision.
Where the rule applies, required protections and human involvement must be designed at the responsible workflow boundary.

## 9. Security and access boundaries

### 9.1 Authentication versus authorization

Authentication establishes identity; authorization decides whether an operation is permitted.
Do not equate class-map discovery, menu visibility, or an admin display with authorization.
Check permissions at the actual data and mutation boundary.
Prevent tenant or role bypasses in direct API, worker, and MCP paths.

### 9.2 Known service ownership

Use `IUsermanager` and relevant framework access-control abstractions in accordance with current framework API contracts.
Keep host-specific identities inside host integration implementations.
Reusable plugins must depend on stable APIs, not the host user model.
An absent user/backend must not default to elevated privileges.

### 9.3 Least privilege

Credentials, database accounts, filesystem permissions, API scopes, and tool exposures MUST be restricted to what their operation requires.
Admin permissions must be explicit.
Write-capable agent tools require server-side authorization even if the AI or user interface requests them.
Do not trust model statements or client-provided role labels as permission evidence.

### 9.4 Confidentiality in transit

Use encrypted authenticated transport for sensitive external communications.
Do not disable TLS verification as a generic compatibility workaround.
Avoid credentials in query strings, browser-visible payloads, analytics events, and log messages.
Document the security and ownership of any internal message bus.

### 9.5 Confidentiality at rest

Choose storage protections proportional to the data and deployment risks.
Protect settings, state, sessions, backups, logs, and caches where they hold sensitive content.
Do not imply base64 encoding or hashing is encryption.
Document key management and rotation where encryption is used.

### 9.6 Secrets

Secrets MUST NOT be committed into sample configurations, documentation, snapshots, tests, or log output.
Use `ConfigValueResolver` or approved credential facilities as designed.
Literal fixed secrets need explicit protected storage where permitted.
Admin read endpoints must not echo existing secrets back to browsers without a proven requirement.

### 9.7 Error handling

Error messages must be actionable without disclosing credentials, database dumps, complete prompts, or personal records.
Detailed diagnostics belong in controlled logs after redaction and minimisation.
Do not leak raw provider HTTP bodies into general users' error screens.

### 9.8 Breach response

Build reliable detection, triage, scope assessment, containment, and evidence collection for data incidents.
Under GDPR Article 33, a controller notifies the supervisory authority without undue delay and, where feasible, within 72 hours after awareness when the notification threshold is met.
Processors notify controllers without undue delay as required by Article 33(2).
Article 34 may require informing affected individuals where the relevant high-risk threshold is met.
Not every security incident is automatically a notifiable personal-data breach.

### 9.9 DPIA and high-risk processing

Identify whether the planned processing is likely to present high risk within GDPR Article 35.
Do not start high-risk processing that requires a DPIA without the controller's documented assessment and required mitigation.
A plugin README must state which decisions depend on deployment context.

### 9.10 Security review boundaries

Treat user-supplied HTML, uploaded files, markdown, prompts, external MCP content, and provider responses as untrusted.
Apply output encoding, input validation, CSRF protection where applicable, and server-side authorization.
Test injection and data exfiltration routes, including indirect prompt injection in retrieval and agent tools.

## 10. International processing and service providers

### 10.1 Provider inventory

For each external recipient record legal entity, service, purpose, endpoint, region, role, categories of data, subprocessors, retention, support access, logging, and deletion mechanisms.
Do not equate an EU endpoint with proof that every support, telemetry, or subprocessor access stays inside the EEA.
Do not assume a provider refrains from model training without contractual/configuration evidence.

### 10.2 GDPR Chapter V transfers

Where personal data is transferred to a third country, assess GDPR Articles 44 to 49.
Adequacy decisions under Article 45 can support transfers within their actual scope.
Article 46 safeguards, including appropriate standard contractual clauses, may be relevant absent adequacy.
Document necessary assessments and supplementary measures where applicable.
Do not treat encryption or a data-processing agreement alone as an automatic transfer authorisation.

### 10.3 Models and AI services

AI requests can include messages, context, attachments, retrieval snippets, tool schemas, tool results, and technical metadata.
Review each outbound projection rather than assuming only the current user prompt is sent.
Minimise model-visible fields before transmission.
Use synthetic data for provider connectivity tests unless real data is justified.
Provider training and retention policies MUST be verified for the selected endpoint and account configuration.

### 10.4 Search, translation, media, and parsers

A generic search, translation, geocoding, image, speech, or document parser is an external processing boundary when configured remotely.
Document the actual transmitted data.
Do not omit a service from the inventory because its tool is technically read-only.
Document temporary files and their removal or retention.

### 10.5 MCP and agent tools

Inbound and outbound MCP can transmit identities, tool arguments/results, resource contents, prompts, and credentials.
Enforce tool-grant scopes and authorization at the server boundary.
Remote tool metadata and annotations are not a replacement for local access checks.
Outbound MCP profiles require recipient, retention, and data-transfer inventory.

### 10.6 Provider deletion

Distinguish local deletion from provider-controlled copies, logs, backups, and artifacts.
Where deletion APIs or contractual mechanisms exist, document what they actually remove.
Do not guarantee deletion of an external recipient's data absent evidence.

## 11. BASE3 architecture mapping

### 11.1 Framework, foundation, implementation, project

The framework owns generic mechanisms and stable APIs.
Foundations define shared contracts and DTOs without choosing deployment-specific legal bases or final storage.
Implementation plugins own concrete persistence, transfer, security, and deletion behavior.
Project plugins and custom bootstraps choose active implementations and host-specific integrations.
The operator assesses actual law, recipients, and retention for the configured composition.

### 11.2 `SettingsStore`

Settings are editable grouped runtime configuration, not a universal place for user-generated personal data.
Settings can nevertheless contain personal data, recipient definitions, and secrets.
Document sensitive setting keys, their visibility, masking, backups, export, and deletion.
Do not store duplicated copies of personal records to make a settings screen easier to build.

### 11.3 `StateStore`

State stores operational markers, locks, cursors, and short-lived values according to established use.
State can contain personal identifiers or tool results and therefore needs classification and retention.
A TTL must be accompanied by an execution/expiry mechanism and scope assessment.
Do not treat an expiring state entry as automatically non-personal.

### 11.4 `Configuration`

Configuration holds static/deployment values.
Do not repurpose it as user storage or mutable runtime privacy controls if `SettingsStore` owns that concern.
Document when configured values include secrets, identifiers, endpoints, or personal paths.

### 11.5 `ConfigValueResolver`

A config-value definition can refer to environment, file, static configuration, or fixed literal values.
Prefer references that keep production credentials out of general editable records when appropriate.
Document how resolved secrets are exposed to runtime components and masked from outputs.

### 11.6 `IClassMap` and configured components

Discovery identifies available implementations, not authorisation or consent.
Configured runtime instances must keep their own data and tenant scopes.
Do not use a second registry as an accessibility or privacy workaround.

### 11.7 Hooks and events

Hook and event payloads MUST contain only data needed by consumers.
Avoid broadcasting entire sensitive objects to every listener.
Document whether listeners can persist, log, or forward payloads.
Event payloads do not bypass the source feature's retention or rights obligations.

### 11.8 Workers and jobs

Workers execute without assuming an interactive user.
Document the technical identity, permissions, triggered data transfers, cleanup, and audit outputs.
Ensure jobs obey subject restrictions and configured tenant boundaries.
A cleanup job must be testable without a production database.

### 11.9 Database migrations

BASE3 must still run without a database; privacy requirements do not change that.
Private technical `ensureTable()` behavior and versioned migrations follow the existing migration architecture.
New tables must define data ownership, deletion, retention, migration rollback considerations, and tests.
Never silently alter broad existing foundation interfaces to implement retention.

### 11.10 Request, routing, middleware

Use `IRequest` rather than direct runtime globals.
Protect state-changing operations against forgery and unauthorised requests at the responsible boundary.
Do not log URL query parameters containing credentials or personal data unless strictly justified.
Host middleware may supply required protections; reusable plugins must document those dependencies.

### 11.11 Displays and templates

Keep display and template paths parallel.
Prepare and minimise data in the display/controller owner.
Render safely, encode output, and implement accessible semantics in the actual template.
Never add a duplicate hidden accessibility view to compensate for an inaccessible primary view.

### 11.12 Assets

Use BASE3 asset resolution to keep privacy-preserving, accessible plugin resources portable.
External fonts, scripts, iframes, analytics, CDNs, and embedded media can create recipient and terminal-storage implications.
List real external loads and avoid unreviewed third-party requests.

## 12. Accessibility baseline and review method

### 12.1 Universally binding BASE3 baseline

All new/modified BASE3 web UIs MUST meet WCAG 2.2 level AA.
This includes administration, forms, dashboards, charts, reports, chatbots, repository objects, modal panels, and embedded widgets.
Where the host imposes stricter requirements, the stricter applicable requirements also apply.
Accessibility must be preserved in standalone and embedded environments.

### 12.2 Legal versus technical verification

WCAG AA conformance is evaluated on actual rendered content and interaction.
EN 301 549 additionally covers other ICT characteristics, including software and documents.
A legal accessibility assessment must match the deployed service, jurisdiction, standard reference, and statutory scope.
BASE3 source tests can provide evidence but cannot certify every possible host styling and configuration.

### 12.3 Perceivable

Content must be available through text, programmatic semantics, adaptable structure, sufficient contrast, and alternatives for non-text media.
Do not use visual arrangement alone to convey essential meaning.
Do not use inaccessible image-only labels, legends, or error signals.

### 12.4 Operable

Every essential action must work with a keyboard and appropriate assistive technology.
Focus must remain visible, predictable, and appropriately sequenced.
No keyboard traps or unescapable modals are permitted.

### 12.5 Understandable

Controls must have clear labels, instructions, state, and errors.
Navigation and interactions should behave consistently.
Important confirmations, expiry warnings, and destructive operations must be understandable before commitment.

### 12.6 Robust

Use semantic HTML and current accessibility APIs correctly.
Custom widgets must expose roles, names, values, and state changes appropriately.
Prefer native elements over custom JavaScript controls when native elements satisfy the requirement.

## 13. HTML semantics and navigation

### 13.1 Document structure

Use meaningful page titles, language attributes, heading hierarchies, landmarks, and section labels.
Maintain a logical reading order independent of visual positioning.
Preserve semantics when host CSS or plugins alter layout.

### 13.2 Headings

Headings describe document hierarchy rather than visual size.
Do not skip heading levels solely for styling.
Use CSS for typography.
Dynamic sections must preserve heading context for assistive technologies.

### 13.3 Landmarks and skip navigation

Expose clear main-content and navigation landmarks.
Provide a usable mechanism to bypass repeated navigation.
Avoid unnecessary duplicated landmarks without labels.
Embedded components must not create conflicting landmark hierarchies.

### 13.4 Links

Link text must communicate purpose in context.
Do not rely on bare "click here" or isolated icon glyphs.
Links and buttons must not be interchanged based on appearance.
Opening a new context should be communicated where appropriate.

### 13.5 Buttons and controls

Every interactive element must have an accessible name.
Use native `button` for actions and `a` for navigation.
Avoid clickable `div` elements and mouse-only event handlers.
Disabled and unavailable behavior must be clearly conveyed.

### 13.6 Language

Set the document language and mark substantive language changes where required.
Do not assume German text is automatically comprehensible under an English language tag.
Accessible labels, descriptions, and error messages must be localisable.

### 13.7 Lists and grouping

Use proper list semantics for actual lists and groups.
Use `fieldset`/`legend` for meaningful form groups.
Do not use tables for purely visual layout.

### 13.8 Data tables

Use real tables with clear header cells and correct header associations.
Indicate sorting state programmatically.
Explain filters and pagination; avoid silent result changes.
Preserve keyboard navigation and screen-reader cell context.

## 14. Keyboard, focus, and interaction

### 14.1 Keyboard reachability

All menus, forms, tabs, dialogs, toolbars, charts controls, date pickers, and chat actions must be keyboard-operable.
Essential functionality cannot depend on hover, drag, long-press, or pointer precision alone.
Document the key interaction model for complex widgets.

### 14.2 Keyboard order

Focus order must follow a meaningful sequence.
Do not use positive `tabindex` to patch a broken DOM order.
Do not send focus to invisible or detached elements.

### 14.3 Visible focus

Focused components must show a visible indicator.
Do not remove outlines without an accessible replacement.
Meet WCAG 2.2 focus appearance/visibility requirements applicable at the selected conformance level.
Check light/dark modes and custom themes.

### 14.4 Focus after DOM changes

When a modal opens, move focus to an appropriate location in the dialog.
When it closes, return focus to the triggering control when it still exists.
When AJAX deletes the focused item, move focus deliberately to a relevant surviving context.
When changing views, communicate the new context to keyboard and screen-reader users.

### 14.5 Dialogs

Use an appropriate dialog role and accessible name.
Modal dialogs must constrain focus correctly without trapping it after closing.
Provide a keyboard-operable close or cancel action.
Never cover a destructive action with a dialog that cannot be dismissed.

### 14.6 Menus and disclosure

Expose expanded/collapsed state and relationships where supported.
Use the interaction pattern appropriate to the real control, not an arbitrary ARIA role.
Avoid hover-only submenus.

### 14.7 Drag and drop

Provide an equivalent accessible non-drag operation for essential reordering or file selection.
Announce the result and provide clear error feedback.
Do not rely on pointer gestures as the sole path.

### 14.8 Timed operations

Provide controls or warnings for time limits where the applicable success criteria require them.
Explain session timeout and recoverable form work.
Background refresh must not unexpectedly steal focus or delete user input.

## 15. Forms, validation, and authentication

### 15.1 Labels and instructions

Provide programmatically associated labels for each input.
Use placeholder text only as supplemental guidance.
Explain required formats, examples, and constraints before submission.
Mark required status in text or accessible semantics, not with colour alone.

### 15.2 Errors

Identify the affected field and describe the error in text.
Connect error messages to their controls.
Provide an accessible summary when several errors occur.
Retain user inputs where safe after validation failure.

### 15.3 Validation timing

Client-side validation cannot be the only validation.
Avoid excessive alerts for every keystroke.
Announce new errors at appropriate times and prevent focus loss.

### 15.4 AJAX submissions

Expose loading/progress states and success/failure feedback through accessible status regions where appropriate.
Do not rely solely on a toast that disappears before a screen reader announces it.
Keep controls usable or clearly disabled while requests run.

### 15.5 Authentication

Authentication screens must support password managers, copy/paste where appropriate, and accessible error feedback.
Do not force cognitive-memory tests as the sole login method.
Apply relevant WCAG 2.2 accessible authentication criteria.
Accessible authentication does not weaken server-side security.

### 15.6 Privacy controls and consent forms

Consent choices must be genuinely actionable and understandable.
Reject/withdraw must not be hidden behind inaccessible or deceptive UI.
The legal consent mechanism must be evaluated under the applicable GDPR/ePrivacy rules.
Consent-state changes must be reflected in the actual processing state.

### 15.7 Destructive actions

Explain consequences and relevant recovery options.
Provide confirmation or review where warranted by the workflow.
Do not rely on colour, icon, or an ambiguous generic `OK` button.

### 15.8 Autocomplete and personal data

Use appropriate HTML autocomplete tokens where applicable and permitted.
Do not disable autocomplete as an arbitrary accessibility or security measure.
If auto-filled personal data is shown, access checks and retention rules still apply.

## 16. Dynamic UI, single-page behavior, and assistive technology

### 16.1 Dynamic content

Changes must be exposed to assistive technologies through appropriate semantics and status messages.
Use live regions sparingly and deliberately.
Avoid announcing large unrelated content blocks on every update.

### 16.2 Loading indicators

Provide text indicating loading when necessary.
Ensure progress controls expose state and do not hide essential errors.
Do not rely on an animation alone.

### 16.3 Pagination and filters

Keyboard users must be able to apply and reset filters.
After an AJAX result refresh, announce result count or completion appropriately.
Keep table headers, page state, and focus coherent.

### 16.4 Tabs and accordions

Use tested accessible patterns.
Communicate selected/expanded states.
Maintain visible labels and supported keyboard navigation.
Do not replace an existing correct pattern with a custom second implementation.

### 16.5 Toasts and notifications

Do not use time-limited inaccessible notifications as the only record of an error.
Choose suitable `status` or `alert` semantics based on severity and urgency.
Avoid interruptive announcements for routine background progress.

### 16.6 Host integration

Embedded components must coexist with host landmarks, focus rules, keyboard shortcuts, CSS resets, and screen-reader output.
Test inside representative host versions where available.
Do not assume standalone accessibility guarantees the same behavior when embedded.

## 17. Colour, layout, and responsive behavior

### 17.1 Colour contrast

Normal text must meet applicable WCAG AA contrast criteria, typically at least 4.5:1.
Large text uses the applicable 3:1 criterion.
Non-text UI component indicators and meaningful graphical objects must meet applicable 3:1 requirements.
Do not infer accessibility from brand colours or theme names.

### 17.2 Information without colour

Use text, pattern, shape, or icon with accessible meaning in addition to colour.
Error/success indicators must remain understandable without colour perception.

### 17.3 Reflow and zoom

Support browser zoom and text resizing without loss of content or functionality under applicable WCAG conditions.
Avoid fixed-height panels that clip text at high zoom.
Test narrow viewports and large fonts.

### 17.4 Spacing and readability

Text content must remain usable when users override line height, letter spacing, word spacing, and paragraph spacing.
Avoid absolute positioning that breaks reading order.

### 17.5 Motion and flashing

Respect reduced-motion preferences where possible.
Do not create flashing content that breaches seizure-related success criteria.
Animations must not be required to understand essential state.

### 17.6 Targets and pointer behavior

Meet relevant WCAG 2.2 target-size requirements and exceptions.
Do not require complex gestures for basic actions.
Support pointer cancellation where applicable.

## 18. Media, documents, reports, and visualisation

### 18.1 Images and icons

Give meaningful images equivalent text alternatives.
Decorative images should not create redundant announcements.
Icon-only controls need explicit accessible names.
Generated images may require human-reviewed alternative text for meaningful content.

### 18.2 Audio and video

Provide captions, transcripts, audio description or suitable alternatives as required for the content and applicable criteria.
Live and prerecorded content have different obligations; assess the actual use.
Do not consider speech-to-text output automatically corrected or complete.

### 18.3 Charts

Charts must have an accessible title, textual summary, and equivalent data access where needed.
Critical values cannot be available only as plotted geometry or colour.
Interactive chart controls must be keyboard-usable with sensible focus/announcement behavior.
Do not duplicate full domain-reporting logic in a separate inaccessible/accessible rendering stack.

### 18.4 Maps and spatial interfaces

Provide text-based access to essential locations, routes, and decisions.
Map-only pointer interaction is insufficient for essential functionality.
Map tiles or provider embeds also create external data-transfer and privacy considerations.

### 18.5 Document exports

Where a plugin generates PDF, office files, or presentations, assess accessible structure, tagging, reading order, text alternatives, headers, tables, document title, and language.
Use the document-specific accessibility criteria applicable to the output and deployment.
A visibly correct document is not necessarily screen-reader accessible.

### 18.6 Markdown and MathJax

Rendered Markdown must preserve semantic headings, lists, links, tables, and code blocks.
Math output must have usable accessible representations where relevant.
Do not inject raw untrusted HTML without proper sanitisation.

### 18.7 Data download

Describe file type, purpose, and any important limitations.
Provide accessible alternatives when a visual-only export is inadequate.
Do not put more personal data into exports than the authorised operation requires.

## 19. AI, chatbots, and agent interfaces

### 19.1 Conversation controls

Message author, role, content, and time should be understandable to assistive technology.
Provide accessible labels for input, send, stop, retry, attach, edit, and delete operations.
Ensure the keyboard can operate every chat feature.

### 19.2 Streaming messages

Do not announce every generated token through an assertive live region.
Offer understandable completion/progress feedback without overwhelming screen readers.
Focus must remain stable while messages grow.

### 19.3 Tool approval

Mutation approval interfaces must identify the proposed action, target, consequences, and available decisions in accessible text.
Do not rely solely on a JSON preview or coloured badge.
Keyboard confirmation must be deliberate and resistant to accidental activation.

### 19.4 Source citations and retrieval

Citations must expose meaningful link text and destination context.
Retrieval-based answers must not expose authorisation-only metadata without necessity.
A citation UI must be operable without a pointer.

### 19.5 Model responses are untrusted presentation data

Sanitise model-produced Markdown and HTML.
Do not execute arbitrary scripts from generated content.
Do not assume AI-produced labels or alt text are accurate without appropriate review.

### 19.6 Accessibility of AI-generated apps

AI-generated forms, dashboards, reports, images, and charts are subject to the same requirements as handwritten ones.
An AI generator MUST produce standards-conforming markup and tests for the resulting component.
Accessibility must not be delegated to an unverified later remediation step.

## 20. Test strategy and measurable acceptance

### 20.1 Accessibility cannot be proved by a single scanner

Automated checks are necessary but insufficient.
Combine static checks, rendered DOM tests, keyboard-only exploration, screen-reader checks, and representative host integration tests.
Track failures against specific success criteria and affected user journeys.

### 20.2 Required automated checks

For changed interactive features, include suitable tests covering accessible names, labels, roles, ARIA relationships, focus state, keyboard interaction, forms, error announcements, and data-table semantics.
Use available automated accessibility tools as aids; they do not replace human review.
Test light/dark themes or other supported theme variants where relevant.

### 20.3 Required manual checks

Verify the feature keyboard-only without a mouse.
Check focus order, visibility, modal behavior, and changes after AJAX updates.
Check meaningful output with at least one supported screen reader/browser combination when the component warrants it.
Review reflow, contrast, target sizes, text scaling, and motion behavior.

### 20.4 Privacy tests

Test data minimisation at serialization and network boundaries.
Test cross-user and cross-tenant authorization.
Test retention and cleanup for active, expired, and partially failed data.
Test export, deletion, soft deletion, restriction, and derived-copy invalidation where relevant.
Test that secrets and personal content do not leak into errors, logs, responses, or snapshots.

### 20.5 Security and retention tests

Test service wiring and intended backend selection.
Test lifecycle with and without an optional database as appropriate for the component.
Test idempotent cleanup and repeated delete calls.
Test backup-restore safeguards at deployment/infrastructure level where relevant.

### 20.6 Source evidence requirement

Tests and documentation must correspond to actual code, not hypothetical APIs.
When an interface or backend is missing, record the limitation and do not manufacture a substitute.
For framework interfaces inspect `framework-api.zip`; for foundation contracts inspect the relevant foundation archive.

### 20.7 Release acceptance record

A release review MUST capture:

- changed feature, owner, and scope;
- data processing and persistence changes or explicit no-impact conclusion;
- legal applicability assumptions requiring operator confirmation;
- affected `PRIVACY.md` and subsystem documentation;
- accessibility impact, relevant WCAG criteria, and tests performed;
- outstanding defects, severity, and approval decision;
- retention/deletion verification where data lifecycle changed;
- evidence and reviewer/date.

### 20.8 Stop-the-line defects

New unbounded retention, unprotected data disclosure, broken deletion, missing required consent, or unauthorized cross-tenant access blocks release.
Critical keyboard-inaccessible workflows, inaccessible required consent/rights controls, and failures against the applicable accessibility acceptance criteria block release.
Do not describe a known blocker as complete with a "follow-up later" note.

## 21. Interface and contract documentation

### 21.1 Complete PHPDoc is mandatory

Every method of a public BASE3 interface MUST have self-contained PHPDoc.
Document purpose, parameter meaning and constraints, return semantics, null/empty behavior, exceptions, side effects, invariants, and any relevant storage or deletion behavior.
When a method affects privacy, its contract MUST state which data it consumes, returns, stores, or deletes as far as that is part of the stable contract.
When a method controls an accessible UI feature, its contract MUST state any relevant accessible behavior.

### 21.2 Avoid unsupported contracts

Do not add methods to widely used framework/foundation interfaces silently.
If a requirement cannot be fulfilled through an existing contract, report the gap and agree on the owning interface/architecture change before implementation.
A feature-plugin interface may be changed only when the entire dependent code is available for a coherent update.

### 21.3 Semantic guarantees

A method named `delete` must document whether it performs logical deletion, physical deletion, schedule-for-deletion, or a provider-side request.
A method named `export` must document scope, authorization expectations, content format, and redaction.
A method named `clear` must not silently imply deletion from backups and external services.
These guarantees must be testable.

## 22. Project and repository responsibilities

### 22.1 Framework repository

Document generic possible storage/processing surfaces with source evidence.
Keep the root `PRIVACY.md` limited to framework behavior and explicit deployment boundaries.
Avoid copying MissionBay-specific tables or provider retention facts into the framework's own inventory.
Maintain this policy under `docs/privacy-accessibility.md`.

### 22.2 Foundation repositories

Document contract-specific data categories and responsibility hand-offs.
Avoid asserting persistence or deletion of a specific implementation when the foundation only specifies an interface.
Document any provided proxy or neutral implementation behavior accurately.

### 22.3 Feature repositories

Document the feature's actual data processing, storage, retention, deletion, export, and UI accessibility.
Keep implementation-specific facts with their owning feature.
Link to shared BASE3 rules instead of cloning the complete policy into every repository.

### 22.4 Implementation repositories

Document backend-specific storage schema, location, access rights, cleanup, and operational controls.
Where the implementation can be swapped, disclose what is not guaranteed by the interface alone.

### 22.5 Project plugins and host adapters

Document final active implementations, providers, regions, recipients, identity mapping, and operator-owned compliance controls.
Clarify whether the host owns session, user identity, access control, audit, and backups.
The final runtime composition determines what is actually processed.

### 22.6 Documentation and README integration

Repository README files SHOULD link `PRIVACY.md` and relevant accessibility/contribution requirements.
If editing an existing README, preserve its structure and content.
Do not invent a new README in a patch when the existing repository README is unavailable for inspection.

## 23. Release workflow and change review

### 23.1 Before design

Read the applicable framework Markdown documentation and exact API contracts.
Inspect provided source across relevant owners for existing comparable functionality.
Identify whether the change introduces storage, external processing, personal data, or interactive UI.
Determine legal-scope questions that require operator or project-owner decisions.

### 23.2 Before coding

List affected owners and artifacts: code, interfaces, tests, migrations, configurations, root `PRIVACY.md`, README, subsystem documentation, and accessibility evidence.
Check existing retention/deletion and accessibility patterns instead of creating parallel ones.
Obtain approval for major architecture/interface changes as already required by BASE3 conventions.

### 23.3 During implementation

Implement the feature and required privacy/accessibility behavior together.
Keep dependency injection, class-map names, templates, assets, and backend ownership consistent.
Update documentation when the behavior changes, not after the feature is merged.
Avoid secrets and personal data in test fixtures.

### 23.4 Before delivering a patch

Run available unit/integration/accessibility checks.
Review deletion, logs, caches, files, provider calls, and authorization.
Check that every new/changed interface method has complete PHPDoc.
Verify that all changed documentation describes observed behavior accurately.

### 23.5 Patch contents

Deliver one coherent `unzip -o` patch rooted at the documented repository path, with no unrelated files.
If necessary source files are unavailable, do not invent their content or claim they were updated.
Explain any incomplete repository-specific update and provide the correct next action.

## 24. Compliance decision records

### 24.1 Legal applicability record

| Question | Required documented answer |
| --- | --- |
| Which service? | Concrete feature/service, host, deployment, and user population |
| Which laws? | GDPR/ePrivacy/WAD/EAA/national provisions actually applicable |
| Which entity? | Controller, processor, provider, public body, economic operator as applicable |
| Which standard? | Published EN version, harmonised legal reference, BASE3 internal WCAG baseline |
| What evidence? | Source revision, tests, runtime configuration, policies and contract references |
| What gaps? | Explicit missing facts, owner, decision, and blocker status |

### 24.2 Exceptions

Exceptions to an internal BASE3 engineering requirement MUST be explicit, narrow, justified, and approved.
A project approval is not a legal waiver.
For legally defined EAA exemptions or undue-burden assessments, follow the exact applicable legislation and documentation duties.
Do not automatically invoke exceptions because compliance is inconvenient or expensive.

### 24.3 Change tracking

Record review date and relevant legal/standards version.
Record source/code revisions associated with technical claims.
Revisit the assessment when new providers, data categories, countries, host versions, or legal instruments become relevant.

## 25. Official legal and standards reference register

The links below are authoritative starting points, not a substitute for checking current consolidated text and national implementing law.

| Instrument | Link | Main use |
| --- | --- | --- |
| GDPR, Regulation (EU) 2016/679 | <https://eur-lex.europa.eu/legal-content/DE/TXT/?uri=CELEX:32016R0679> | Personal-data processing and data-subject rights |
| ePrivacy Directive 2002/58/EC | <https://eur-lex.europa.eu/eli/dir/2002/58/oj> | Communications and terminal-equipment privacy |
| EAA, Directive (EU) 2019/882 | <https://eur-lex.europa.eu/eli/dir/2019/882/oj> | Covered accessible products/services |
| Web Accessibility Directive (EU) 2016/2102 | <https://eur-lex.europa.eu/eli/dir/2016/2102/oj> | Public-sector websites/apps |
| Implementing Decision (EU) 2021/1339 | <https://eur-lex.europa.eu/eli/dec_impl/2021/1339/oj> | EN 301 549 v3.2.1 harmonisation under WAD |
| EN 301 549 update, September 2026 | <https://accessible-eu-centre.ec.europa.eu/content-corner/news/european-accessibility-standard-en-301-549-has-been-updated-2026-09-07_en> | Published v4.1.1 versus legal reference |
| WCAG 2.2 | <https://www.w3.org/TR/WCAG22/> | BASE3 A/AA engineering benchmark |
| EDPB Article 25 guidelines | <https://www.edpb.europa.eu/documents/guideline/guidelines-42019-on-article-25-data-protection-by-design-and-by-default_en> | Privacy by design/default |
| EDPB controller/processor guidelines | <https://www.edpb.europa.eu/documents/guideline/guidelines-072020-on-the-concepts-of-controller-and-processor-in-the-gdpr_en> | Responsibility assessment |
| EU adequacy decisions | <https://commission.europa.eu/law/law-topic/data-protection/international-dimension-data-protection/adequacy-decisions_en> | International transfers |
| EU SCC Decision (EU) 2021/914 | <https://eur-lex.europa.eu/eli/dec_impl/2021/914/oj> | Appropriate transfer safeguards |
| German BFSG | <https://www.gesetze-im-internet.de/bfsg/> | EAA national implementation |
| German BFSGV | <https://www.gesetze-im-internet.de/bfsgv/> | Technical national requirements |
| German BGG | <https://www.gesetze-im-internet.de/bgg/> | Public-sector accessibility duties |
| German BITV 2.0 | <https://www.gesetze-im-internet.de/bitv_2_0/> | Federal public-sector web/app requirements |
| German TDDDG | <https://www.gesetze-im-internet.de/ttdsg/> | Terminal-equipment privacy law |
| German BDSG | <https://www.gesetze-im-internet.de/bdsg_2018/> | Supplementary German data-protection law |

## 26. Final acceptance checklist

The following checklist is mandatory for every feature that changes relevant behavior.

### Data and privacy

- [ ] Applicable law and concrete processing roles assessed; unknown deployment facts marked.
- [ ] Purpose and legal-basis ownership documented.
- [ ] Every new data category classified and minimised.
- [ ] Every persistent location inventoried, including logs and derived data.
- [ ] Access, authorisation, and tenant scope verified.
- [ ] Retention period/criterion, trigger, cleanup owner, and failure behavior documented.
- [ ] Deletion route and propagation tested where required.
- [ ] External recipients/regions, contracts, provider retention, and transfer dependencies documented.
- [ ] Sensitive values and credentials excluded from routine logs, errors, and test output.
- [ ] Root `PRIVACY.md` updated with source-backed facts.

### Accessibility

- [ ] WCAG 2.2 A/AA assessment completed for changed UI and relevant workflows.
- [ ] Semantic structure, labels, language, headings, and landmark roles verified.
- [ ] Keyboard navigation and visible focus verified end to end.
- [ ] Dialog, AJAX, live-update, and error announcement behavior verified.
- [ ] Colour contrast, reflow, text scaling, and responsive behavior verified.
- [ ] Charts, reports, media, and generated documents assessed where relevant.
- [ ] Automated accessibility checks and targeted manual assistive-technology tests recorded.
- [ ] Host integration checked where relevant.

### Architectural and documentation completeness

- [ ] Existing code and contracts inspected for overlap and correct owner.
- [ ] No duplicated logic, compensating service, second registry, or parallel UI added.
- [ ] Framework/foundation contracts unchanged unless expressly approved.
- [ ] Every new/changed interface method fully documented in code.
- [ ] Code, tests, migrations, configuration, examples, README, and subsystem docs aligned in this change.
- [ ] Claims are evidence-based; unresolved compliance defects clearly identified as blockers.

## 27. Compliance verification matrices

These mappings help reviewers translate external requirements into engineering evidence. The authoritative text of each instrument controls its interpretation. A mapping is not a substitute for checking conditions, exceptions, effective dates and national implementation.

### 27.1 GDPR-to-engineering evidence matrix

| GDPR provision | Required review evidence | BASE3 owner |
| --- | --- | --- |
| Article 5(1)(a) | Identified purpose and lawful/fair/transparent processing | Operator and domain feature |
| Article 5(1)(b) | Data fields used only for specific documented purposes | Domain feature |
| Article 5(1)(c) | Minimal request, DTO, log, audit and export projections | Data-producing owner |
| Article 5(1)(d) | Correction path to authoritative and derived data | Storage/domain owner |
| Article 5(1)(e) | Retention criterion, expiry trigger, cleanup implementation | Storage/feature owner |
| Article 5(1)(f) | Authorisation, confidentiality, integrity and security tests | Backend and host |
| Article 5(2) | Traceable compliance evidence and ownership | Controller/operator |
| Article 6 | Processing-purpose legal basis and operator decision | Controller/operator |
| Article 7 | Consent capture/withdrawal only where consent is basis | Controller and UI owner |
| Article 8 | Child consent conditions assessed when applicable | Controller/operator |
| Article 9 | Special-category safeguards and applicable exception assessed | Controller and data owner |
| Articles 12-14 | Information/notice and understandable rights surfaces | Controller and UI owner |
| Article 15 | Authorised subject access across relevant records | Data owners/operator |
| Article 16 | Correction and index/cache refresh | Owning feature |
| Article 17 | Erasure decisions, exceptions, deletion propagation | Controller, data owners |
| Article 18 | Restriction controls when legally required | Owning feature |
| Article 20 | Applicable portability and interoperable export | Owning feature/operator |
| Article 21 | Applicable objection and suppression workflow | Owning feature/operator |
| Article 22 | Review of solely automated significant decisions | Operator and workflow owner |
| Article 24 | Accountable governance and risk measures | Controller/operator |
| Article 25 | Data protection by design and default tests | All implementing owners |
| Article 28 | Processor contracts and instructions where relevant | Operator/vendor owner |
| Article 30 | Processing records where required | Controller/processor |
| Article 32 | Security controls based on actual risk | Backend, host, operator |
| Article 33 | Incident classification and notification workflow | Controller; processor alerts controller |
| Article 34 | High-risk communication process where applicable | Controller/operator |
| Article 35 | DPIA gate for high-risk processing | Controller/operator |
| Articles 37-39 | DPO involvement where required | Controller/processor |
| Articles 44-49 | Recipient, country, transfer mechanism evidence | Operator/vendor owner |

### 27.2 Accessibility law and evidence matrix

| Instrument | Applicability question | Required technical or legal evidence |
| --- | --- | --- |
| Directive (EU) 2016/2102 | Is the operator a covered public-sector body, and is this a covered site/app? | Applicable national requirements, accessibility statement, monitoring and remediation |
| Directive (EU) 2019/882 Article 2 | Is the product/service in one of the defined consumer categories? | Documented scope decision, offerings and market |
| Directive (EU) 2019/882 Article 4, Annex I | Which accessibility requirements cover the product/service? | Traceable functional verification and user journey tests |
| Directive (EU) 2019/882 Article 14 | Has a fundamental-alteration/disproportionate-burden exception been claimed? | Qualified and documented assessment under law |
| Directive (EU) 2019/882 Article 32 | Does a relevant transitional arrangement apply? | Dated product/contract facts and applicable expiry |
| EN 301 549 v3.2.1 | Is its harmonised citation applicable in the relevant regime? | Legal reference and conformance scope |
| EN 301 549 v4.1.1 | Is this published version cited in the OJ for the regime now? | Dated OJ check and product/software evaluation |
| WCAG 2.2 AA | Has every applicable A/AA success criterion been tested? | Automated, manual and assistive technology evidence |
| German BFSG/BFSGV | Covered product/service and exception/transitional rules? | National assessment, accessibility information and tests |
| German BGG/BITV | Covered federal/state/public body and digital offering? | Applicable national statement and feedback routes |

### 27.3 WCAG 2.2 A/AA verification index

The following are reminders for tests, not reproductions of the normative W3C success criteria. The exact standard text and its stated exceptions govern.

| Criterion | Level | Concrete verification focus |
| --- | --- | --- |
| 1.1.1 Non-text Content | A | Appropriate text alternatives, decorative exceptions |
| 1.2.1 Audio-only and Video-only (Prerecorded) | A | Appropriate alternative for prerecorded standalone media |
| 1.2.2 Captions (Prerecorded) | A | Captions for applicable prerecorded synchronized media |
| 1.2.3 Audio Description or Media Alternative (Prerecorded) | A | Equivalent alternative for visual information |
| 1.2.4 Captions (Live) | AA | Live captions for applicable synchronized media |
| 1.2.5 Audio Description (Prerecorded) | AA | Prerecorded visual information described as required |
| 1.3.1 Info and Relationships | A | Semantic headings, labels, tables and group structure |
| 1.3.2 Meaningful Sequence | A | Reading order without visual CSS assumptions |
| 1.3.3 Sensory Characteristics | A | Instructions independent of shape, position or sound |
| 1.3.4 Orientation | AA | No unjustified forced screen orientation |
| 1.3.5 Identify Input Purpose | AA | Programmatic identification of applicable personal-data input purpose |
| 1.4.1 Use of Color | A | All essential distinctions also conveyed non-visually |
| 1.4.2 Audio Control | A | Controls for automatically played audio as required |
| 1.4.3 Contrast (Minimum) | AA | Text contrast and applicable exceptions |
| 1.4.4 Resize Text | AA | Content/functionality at relevant text enlargement |
| 1.4.5 Images of Text | AA | Prefer live text; apply stated exceptions |
| 1.4.10 Reflow | AA | No unnecessary two-dimensional scrolling or clipping |
| 1.4.11 Non-text Contrast | AA | UI controls and essential graphics contrast |
| 1.4.12 Text Spacing | AA | User spacing overrides do not lose content/function |
| 1.4.13 Content on Hover or Focus | AA | Dismissible, hoverable and persistent additional content where required |
| 2.1.1 Keyboard | A | Functionality operable by keyboard interfaces |
| 2.1.2 No Keyboard Trap | A | Enter and exit every reachable interactive area |
| 2.1.4 Character Key Shortcuts | A | Appropriate controls for single-character shortcuts |
| 2.2.1 Timing Adjustable | A | Applicable time limits adjustable/extendable/avoidable |
| 2.2.2 Pause, Stop, Hide | A | Moving/auto-updating content controllable where required |
| 2.3.1 Three Flashes or Below Threshold | A | No seizure-triggering flashes |
| 2.4.1 Bypass Blocks | A | Skip repeated navigation blocks |
| 2.4.2 Page Titled | A | Descriptive document/page titles |
| 2.4.3 Focus Order | A | Logical sequential navigation through operations |
| 2.4.4 Link Purpose (In Context) | A | Purpose clear from link/context |
| 2.4.5 Multiple Ways | AA | Alternative paths to pages within applicable sets |
| 2.4.6 Headings and Labels | AA | Clear descriptive headings and control labels |
| 2.4.7 Focus Visible | AA | Focus indication on keyboard controls |
| 2.4.11 Focus Not Obscured (Minimum) | AA | Focus not completely concealed by authored overlays |
| 2.5.1 Pointer Gestures | A | Simple pointer alternative for multipoint/path gestures |
| 2.5.2 Pointer Cancellation | A | Avoid unintended completion on pointer down |
| 2.5.3 Label in Name | A | Accessible name includes visible label text |
| 2.5.4 Motion Actuation | A | Alternative input for movement-triggered actions |
| 2.5.7 Dragging Movements | AA | Non-drag alternative for dragging functionality |
| 2.5.8 Target Size (Minimum) | AA | Appropriate pointer targets or stated exceptions |
| 3.1.1 Language of Page | A | Programmatic primary language |
| 3.1.2 Language of Parts | AA | Programmatic language changes when required |
| 3.2.1 On Focus | A | No unexpected context changes on focus |
| 3.2.2 On Input | A | No unannounced context changes from input |
| 3.2.3 Consistent Navigation | AA | Stable repeated navigation order |
| 3.2.4 Consistent Identification | AA | Consistent component meaning and identification |
| 3.2.6 Consistent Help | A | Consistent placement for repeated help mechanisms |
| 3.3.1 Error Identification | A | Errors identified in accessible text |
| 3.3.2 Labels or Instructions | A | Inputs have adequate guidance |
| 3.3.3 Error Suggestion | AA | Suggestions where appropriate and required |
| 3.3.4 Error Prevention (Legal, Financial, Data) | AA | Review/confirmation/reversal where criteria apply |
| 3.3.7 Redundant Entry | A | Avoid unnecessary repeated input in a process |
| 3.3.8 Accessible Authentication (Minimum) | AA | Accessible alternative to restricted cognitive tests |
| 4.1.2 Name, Role, Value | A | Semantic control API names, roles, states and updates |
| 4.1.3 Status Messages | AA | Programmatic announcements without forced focus changes |

`4.1.1 Parsing` is obsolete/removed in WCAG 2.2 and MUST NOT be treated as a current independent WCAG 2.2 conformance criterion. This does not excuse invalid markup when it breaks other criteria.
The criterion `2.4.13 Focus Appearance` is level AAA, not an AA requirement; follow it as an enhancement where feasible, not as falsely stated statutory AA conformance.

### 27.4 Sample review scenarios

For each scenario below, record actual test evidence and source revision rather than marking it complete from a visual inspection.

1. Create a record with a keyboard, then correct validation errors and save without losing focus.
2. View a protected data table as an authorised user and verify another tenant cannot retrieve the same rows directly.
3. Change a group or role and verify direct API authorization, not only hidden navigation controls.
4. Delete a user-owned document and inspect primary storage, attachments, indexes and caches for the documented final state.
5. Restore a backup into a controlled environment and verify outstanding deletions remain effective.
6. Trigger an asynchronous cleanup job twice and verify stable, safe repeated results.
7. Open and close an AJAX modal with keyboard and screen reader; verify labels, focus return and announcements.
8. Use a chart without colour vision or a pointer; verify full access to essential values.
9. Submit an invalid consent/rights form; verify accessible error descriptions and no premature provider calls.
10. Change provider configuration and inspect transmitted payload fields, routing region and diagnostics.
11. Confirm a denied export/deletion action cannot be performed by direct URL or worker execution.
12. Use the interface at 200%/400% zoom and with enlarged spacing without clipped critical controls.

---

## 28. Summary of mandatory principles

**Privacy is part of the feature contract, not an afterthought.**
**Data lifecycle includes collection, use, copies, recipients, retention, deletion, and restoration.**
**Every repository maintains an accurate, source-backed root `PRIVACY.md`.**
**Every BASE3-provided interactive UI meets WCAG 2.2 AA as an internal minimum.**
**Applicable EU and national law is a release constraint, not optional guidance.**
**Architecture remains clear: the existing owner implements the responsibility.**
**Code, tests, privacy records, accessibility evidence, and documentation change together.**
