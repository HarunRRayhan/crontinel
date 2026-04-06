# Crontinel — CLAUDE.md

> **This is the OSS package repo only.**
> Full canonical context is in `AI_CONTEXT.md` — read that first before any work.
> Do not reference other workspace projects (reddit-marketing, crontinel-landing, etc.) from here.

## Scope of this repo

`harunrrayhan/crontinel` — MIT Laravel package. Ships to Packagist.
SaaS app is at `~/Work/crontinel/app` (private repo, TBD).
Landing page is at `~/Work/crontinel/landing`.

## Quick reference

- Namespace: `Crontinel\`
- Config key: `config('crontinel.*')`
- Artisan commands: `crontinel:install`, `crontinel:check`
- Tests: `vendor/bin/pest`
- Current milestone: **Milestone 1 — OSS Package MVP** (see `PRD.md` section 14)
- All architectural decisions: see `ARCHITECTURE.md`
- All dev conventions: see `AI_CONTEXT.md`

## Response Footer (Telegram / OpenClaw)

Every message sent to the user in Telegram must end with:

```
⏰ ct || 🤖 {model}
```

One blank line before the footer. No exceptions — short replies, confirmations, everything.
