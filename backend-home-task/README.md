## Introduction
This a base for Debricked's backend home task. It provides a Symfony skeleton and a Docker environment with a few handy 
services:

- RabbitMQ
- MySQL (available locally at 3307, between Docker services at 3306)
- MailHog (UI available locally at 8025)
- PHP
- Nginx (available locally at 8888, your API endpoints will accessible through here)

See .env for working credentials for RabbitMQ, MySQL and MailHog.

A few notes:
- By default, emails sent through Symfony Mailer will be sent to MailHog, regardless of recipient.

## How to use the Docker environment
### Starting the environment
`docker compose up`

### Stopping the environment
`docker compose down`

### Running PHP based commands
You can access the PHP environment's shell by executing `docker compose exec php bash` (make sure the environment is up 
and running before, or the command will fail) in root folder.

We recommend that you always use the PHP container's shell whenever you execute PHP, such as when installing and 
requiring new composer dependencies.

## Upload & Scan API
- `POST /api/uploads` accepts `multipart/form-data` containing one or more dependency files (e.g. `composer.lock`). Optional form fields `email` and `slack_webhook` attach notification preferences to the batch.
- `GET /api/uploads` lists stored uploads with status, vulnerability counts, and rule-engine metadata.
- `GET /api/uploads/{id}` returns a single upload, including the most recent Debricked response payload and notification history.
- `GET /api/scan/{scanId}` fetches the persisted Debricked status payload for a given scan identifier.

Only Debricked-supported dependency manifests are accepted. Unsupported files are skipped and logged without aborting the request.

## Async Processing
1. Uploads are persisted via `FileUploadService` and dispatched to the Messenger `async` transport.
2. `ProcessUploadMessageHandler` streams the file to Debricked, stores the returned identifiers, and initialises a `ScanResult` record.
3. `CheckScanStatusMessageHandler` polls Debricked until completion, updating vulnerability counts and caching the raw response payload in JSON.
4. Updated entities keep notification metadata (`scanResultPayload.metadata`) and rule-engine state (`scanResultPayload.ruleState`).

## Rule Engine & Notifications
- Rules are defined in `App\RuleEngine\RuleEngine` and evaluated by the console command `app:rules:run`.
- Triggers:
  - `high_vulnerability`: completed scan exceeds `RULE_ENGINE_HIGH_VULN_THRESHOLD`.
  - `upload_failed`: upload transitions to `error`.
  - `upload_stuck`: upload remains `queued`, `uploading`, or `processing` longer than `RULE_ENGINE_STUCK_MINUTES`.
- Actions:
  - Email via MailHog-compatible SMTP (defaults to `mailhog:1025`).
  - Slack webhook POST (per-upload override or `SLACK_DEFAULT_WEBHOOK`).

Each trigger records its execution under `scanResultPayload.ruleState.notifiedRules` to avoid duplicate notifications.

Run the evaluator in the PHP container:

```bash
php bin/console app:rules:run
```

## Configuration
Set or override the following environment variables (see `.env`):

- `DEBRICKED_API_BASE_URL`, `DEBRICKED_API_TOKEN`
- `NOTIFICATION_FROM_EMAIL`
- `RULE_ENGINE_HIGH_VULN_THRESHOLD`
- `RULE_ENGINE_STUCK_MINUTES`
- `SLACK_DEFAULT_WEBHOOK`
- `SMTP_HOST`, `SMTP_PORT`
- `RULE_ENGINE_INTERVAL_SECONDS` (seconds between background evaluations; default 60)

## Testing
Execute the PHPUnit suite (Docker PHP container recommended):

```bash
./vendor/bin/simple-phpunit --filter RuleEngineTest
```
