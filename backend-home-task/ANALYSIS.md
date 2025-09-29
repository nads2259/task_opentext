# Backend Home Task – Repository Analysis

## Overview
- Symfony 6.1 service that ingests dependency manifests, forwards them to Debricked, and stores the scan lifecycle.
- Docker Compose stack includes PHP-FPM, Nginx, MySQL 8, RabbitMQ, and MailHog. Credentials and DSNs live in `.env`.
- Uploads are persisted in MySQL, processed asynchronously through Symfony Messenger, and enriched with notification metadata stored in the JSON `scanResultPayload` column.

## Application Structure
- **Controllers** (`src/Controller`)
  - `HomeController` provides a health probe.
  - `UploadController` handles batched uploads, listings, and individual status queries while surfacing notification metadata.
  - `ScanController` exposes Debricked scan payloads via `scanId`.
  - `RepositoryController` now offers a repository-style view over stored uploads plus their scans.
- **Services** (`src/Service`)
  - `FileUploadService` validates manifest filenames, stores them on disk, captures notification preferences, and dispatches background work.
  - `DebrickedClient` POSTs dependency files and polls Debricked’s “Dependency files management” endpoints.
  - `NotificationService` delivers MailHog-compatible SMTP messages and Slack webhook posts.
- **Messaging** (`src/Message*`)
  - `UploadFileMessage` → `UploadFileMessageHandler` marks uploads as queued.
  - `ProcessUploadMessage` → `ProcessUploadMessageHandler` streams files to Debricked and seeds `ScanResult` entities.
  - `CheckScanStatusMessage` → `CheckScanStatusMessageHandler` polls Debricked until completion and updates vulnerability counts plus cached payloads.
- **Rule Engine** (`src/RuleEngine`)
  - Hard-coded triggers live in `RuleEngine`; notification results persisted under `scanResultPayload.ruleState`.
  - `app:rules:run` command evaluates outstanding uploads and dispatches notifications.

## Persistence Model
- `UploadedDependencyFile` retains basic metadata, Debricked identifiers, vulnerability counts, and a JSON payload for notification state.
- `ScanResult` tracks Debricked `scanId`, status, human-readable summary (JSON-encoded), and timestamps with cascade delete to the parent upload.
- No schema changes were introduced beyond existing migrations; new metadata rides on the JSON column to avoid altering the immutable entity definition.

## Async & Notification Flow
1. HTTP upload stores the file under `var/uploads/` and records notification preferences.
2. `ProcessUploadMessageHandler` uploads the artifact to Debricked, marks the upload `processing`, and logs the initial response.
3. `CheckScanStatusMessageHandler` repeatedly polls Debricked, updating counts and caching the latest payload until the scan completes.
4. `app:rules:run` evaluates rules:
    - High vulnerability threshold (`RULE_ENGINE_HIGH_VULN_THRESHOLD`).
    - Upload failures.
    - Uploads stuck longer than `RULE_ENGINE_STUCK_MINUTES`.
   Actions dispatch email (MailHog SMTP) and/or Slack webhook messages. Each trigger is recorded to prevent duplicate notifications.

## Remaining Risks & Notes
- Upload error handling currently stops the pipeline but leaves notification delivery to the rule engine pass; ensure the command is scheduled in production.
- SMTP notifications rely on a minimal socket implementation; adjust host/port via `SMTP_HOST` and `SMTP_PORT` if the MailHog setup changes.
- Real Debricked credentials must be supplied through environment overrides (`DEBRICKED_API_TOKEN`).

## Implementation Summary
- Refactored upload workflow to capture per-upload notification metadata without altering the database schema.
- Added a lightweight rule engine, notification service, and background command to satisfy trigger/action requirements.
- Enhanced Messenger handlers to align with the immutable Doctrine entities and to persist Debricked payloads for inspection.
- Documented configuration knobs and provided PHPUnit coverage (`tests/RuleEngine/RuleEngineTest.php`) for the rule evaluator.
