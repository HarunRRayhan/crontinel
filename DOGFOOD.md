# Crontinel — Founder Dogfooding Checklist

Run this against the **OSS package** in your local/staging Laravel app. Use it before every release and as a daily quick-check habit.

---

## 1. Onboarding (first-time install path)

- [ ] `composer require crontinel/laravel` in a clean Laravel app
- [ ] Run `php artisan vendor:publish --tag=crontinel-config` — verify config file appears
- [ ] Run `php artisan crontinel:install` — confirm no errors, migrations run cleanly
- [ ] Open Blade dashboard URL (`/crontinel` or configured prefix) — verify it loads without auth errors
- [ ] Confirm no JS console errors on dashboard page
- [ ] Connect a second app (simulate multi-app path by changing `APP_NAME` in `.env`) — confirm separate entry in dashboard

**Friction check:** Note any step that took >30s to understand or required docs. Log it.

---

## 2. Horizon Monitoring Accuracy

- [ ] Start Horizon: `php artisan horizon`
- [ ] Confirm dashboard shows Horizon status = `running`
- [ ] Dispatch a test job that takes >1s
- [ ] Confirm queue depth increments correctly before job processes
- [ ] Confirm job count in "active" drops after processing
- [ ] Kill one supervisor (`php artisan horizon:terminate` on a specific supervisor)
- [ ] Confirm dashboard reflects supervisor down within the polling interval
- [ ] Restart Horizon — confirm status flips back to `running`
- [ ] Artificially fail a job (throw inside handle())
- [ ] Confirm failed count increments on the correct queue row
- [ ] Confirm failed_jobs table has the entry

---

## 3. Queue Depth & Backlog Alerts

- [ ] Set a queue depth threshold (e.g., `depth_alert: 5` in config)
- [ ] Dispatch 6 jobs without processing — confirm alert fires
- [ ] Let queue drain — confirm "resolved" notification fires (if configured)
- [ ] Set `oldest_job_age_alert: 60` (60 seconds)
- [ ] Dispatch a job, pause Horizon, wait 65s — confirm age alert fires
- [ ] Confirm alert channels respect config (`slack_webhook_url`, `mail`)

---

## 4. Cron / Scheduled Command Monitoring

- [ ] Add a test scheduled command: `$schedule->command('inspire')->everyMinute()`
- [ ] Run `php artisan schedule:run` — confirm run is recorded in crontinel history
- [ ] Check dashboard: last run, duration, exit code all populated correctly
- [ ] Create a command that exits with code 1 — confirm it shows as failed in history
- [ ] Add a command with `withoutOverlapping()` — run two overlapping instances — confirm only one run is recorded
- [ ] Check "late" detection: configure a command to run every 5 min, skip one cycle — confirm dashboard marks it as late

---

## 5. CLI Health Check

- [ ] `php artisan crontinel:health` — confirm output shows current Horizon + queue + cron status
- [ ] Run with `--format=json` — confirm JSON output is parseable
- [ ] Run with Horizon stopped — confirm exit code is non-zero
- [ ] Run with a high queue depth (above threshold) — confirm exit code is non-zero
- [ ] Pipe to `jq` or parse — confirm suitable for CI integration

---

## 6. Alert Channels (local test)

- [ ] Set up a test Slack webhook in `.env` (`CRONTINEL_SLACK_WEBHOOK`)
- [ ] Trigger a failure — confirm Slack message arrives with correct format
- [ ] Set up mail (`log` driver) — trigger failure — confirm mail shows in `storage/logs/laravel.log`
- [ ] Test PagerDuty integration key if configured — confirm event reaches PagerDuty events API
- [ ] Test webhook channel — confirm POST is sent to configured URL with correct payload shape
- [ ] Confirm alert deduplication: same alert does not fire twice within cooldown window

---

## 7. Daily Founder-Use Workflow (2-min routine)

Do this each morning against the staging app:

1. Run `php artisan crontinel:health` — status should be all green
2. Open dashboard — scan for any `alert` or `late` rows
3. Check failed job count on each queue — should be 0
4. Check queue depth on `emails`, `default`, `notifications` — all below threshold
5. Confirm last scheduled command runs match expected schedule (e.g., `send-invoices` ran last night)
6. If anything is yellow/red: open the detail view, read the error, fix or log in `BLOCKERS.md`

---

## 8. Pre-Release Gate

Before tagging any release, run through these checks in order:

- [ ] Sections 1–6 above: all checkboxes green
- [ ] `php artisan test` — all tests pass
- [ ] `./vendor/bin/pint --test` — zero lint errors
- [ ] Dashboard loads with no JS errors on Chrome and Firefox
- [ ] Config file has no breaking changes (new keys have defaults)
- [ ] `CHANGELOG.md` updated with what changed
- [ ] Version bumped in `composer.json`

---

## Known Friction Log

Document rough edges found during dogfooding here so they become tickets:

| Date | Step | Issue | Priority |
|------|------|-------|----------|
| — | — | — | — |

Add a row each time you find something that confused you, felt slow, or required docs that shouldn't be needed.
