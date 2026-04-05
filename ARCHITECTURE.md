# Crontinel — Architecture Decisions

**Last updated:** 2026-04-05 (rev 2)
**Status:** Approved, pre-coding — PRD in progress

---

## Product model

**Option C — OSS package + hosted SaaS (Sentry model)**

- Free, MIT-licensed Laravel package anyone can self-host
- Hosted SaaS at app.crontinel.com for teams who don't want to manage it
- OSS package = distribution engine (Packagist downloads → inbound signups)
- SaaS = revenue engine

---

## Domain structure

| Domain | Purpose | Stack |
|---|---|---|
| `crontinel.com` | Marketing / landing page | Astro on CF Pages |
| `app.crontinel.com` | Hosted SaaS dashboard | Laravel 12 on Hetzner/AWS |
| `staging.crontinel.com` | Staging environment | Same stack as app, separate server |
| `docs.crontinel.com` | Documentation | Astro Starlight on CF Pages |
| `status.crontinel.com` | Status page | Gatus on Hetzner VPS |

---

## Repositories

| Repo | Visibility | Purpose |
|---|---|---|
| `github.com/HarunRRayhan/crontinel` | Public (MIT) | OSS Laravel package |
| `github.com/HarunRRayhan/crontinel-app` | Private | SaaS app (Laravel 12) |
| `github.com/HarunRRayhan/crontinel-landing` | Public (or Private TBD) | Astro landing page |

OSS package repo is public from day one — it's the distribution engine. SaaS app is private. Landing page repo visibility TBD (no sensitive data in it).

---

## Pricing

| Tier | Price | Limits |
|---|---|---|
| **Free forever** | $0 | 1 app, 5 monitors, 7-day history |
| **Pro** | $19/mo | 5 apps, unlimited monitors, 90-day history, Slack/PagerDuty alerts, API access |
| **Team** | $49/mo | Unlimited apps + users, 1-year history, priority support, status page |

- New signup → 14-day Pro trial, no credit card required
- After trial → drops to Free unless upgraded
- Billing is per **team**, not per user
- No per-seat pricing (keeps it simple)
- **Annual discount: 20% off** (≈ 2 months free) — Pro $182/yr, Team $470/yr

**Competitive anchor:** Cronitor $20/mo (20 monitors, generic). We give deeper Laravel-native insight at same price.

---

## Stack

### Landing page (`crontinel.com`)
- **Astro** + Tailwind CSS — hybrid rendering
- Core pages (`/`, `/pricing`, `/features`, `/about`) → `prerender=true` (static, zero Worker cost)
- SEO pages (`/blog/*`, `/vs/*`, `/use-cases/*`, `/integrations/*`) → `prerender=false` (on-demand SSR via CF Worker)
- Content source: MDX files in repo (no CMS dependency to start)
- Deployed to **Cloudflare Pages free tier** — 100K Worker req/day, no file count issues with SSR approach
- Email capture → Resend (free tier: 3K/mo)
- Upgrade path: CF Workers Paid ($5/mo) when >100K req/day — no code changes needed

### SaaS app (`app.crontinel.com`)
- **Laravel 12** (PHP 8.2+)
- **Livewire 3** + Alpine.js — real-time dashboard, no JS build step
- **PostgreSQL** — primary database (founder is a Postgres expert, always Postgres)
- **Redis** — queues + cache (start here; SQS migration path available later)
- **Laravel Horizon** — queue worker management (dogfooding)
- **Laravel Cashier** — Stripe billing
- **Laravel Breeze** — auth (email/password + social TBD)

### OSS package (`harunrrayhan/crontinel` on Packagist)
- Pure Laravel package, no external dependencies beyond Laravel itself
- Self-contained Blade dashboard
- Works without the SaaS — fully functional standalone

---

## Infrastructure

Founder is a DevOps engineer — handles server provisioning, Nginx, PHP-FPM, Redis, and deployments manually. No Coolify or Forge needed.

### If AWS credits approved (preferred)

| Component | Choice | Notes |
|---|---|---|
| App server | EC2 (t3.small or t3.medium) | PHP-FPM + Nginx |
| Database | RDS PostgreSQL or Aurora PostgreSQL Serverless | Separate managed, not on EC2 |
| Cache + queues | ElastiCache Redis | Start here; migrate to SQS later if needed |
| Email | Resend (start) → SES (once production access approved) | Free both ways |
| Landing page | Cloudflare Pages (free) | Static Astro |
| Docs | Cloudflare Pages (free) | Astro Starlight |
| Status page | Gatus on EC2 | Separate Docker container |
| SSL | ACM (free with ALB) or Let's Encrypt | |

**DB fallback within AWS:** If Aurora Serverless cost is unpredictable early on, start with RDS PostgreSQL t3.micro (free tier eligible).

### If Hetzner (fallback, no AWS credits)

| Component | Choice | Cost/mo |
|---|---|---|
| App server | Hetzner CX22 (2 vCPU, 4GB) | ~€4 |
| Database | **Neon** (serverless Postgres, free tier: 0.5GB) | $0 → $19 when scaling |
| Cache + queues | Redis on VPS | $0 |
| Email | Resend (start, free: 3K/mo) → SES when AWS ready | $0 |
| Landing + Docs | Cloudflare Pages (free) | $0 |
| Status page | Gatus on same VPS | $0 |
| SSL | Let's Encrypt (certbot) | $0 |
| **Total** | | **~€4–23/mo** |

**Why Neon over self-hosted Postgres on Hetzner:** Neon gives managed backups, point-in-time recovery, and branching (useful for staging) without paying for a separate DB server. Free tier covers early stage.

**Alternative to Neon:** Supabase free tier (500MB Postgres, also managed).

---

## Multi-tenancy model

```
users           id, name, email, password, ...
teams           id, name, slug, plan (free/pro/team)
team_user       team_id, user_id, role (owner/admin/member)
apps            id, team_id, name, slug, api_key, timezone
monitors        id, app_id, type (horizon/queue/cron), name, config JSON
monitor_events  id, monitor_id, status, payload JSON, occurred_at
alerts          id, app_id, channel (slack/mail/webhook), config JSON
subscriptions   (managed by Laravel Cashier — on teams table)
```

- **One team → many users** (devs collaborating on same account)
- **One team → many apps** (agency or founder with multiple products)
- Billing attaches to the **team**, not the user

---

## OSS vs SaaS feature split

| Feature | OSS | SaaS Free | SaaS Pro | SaaS Team |
|---|---|---|---|---|
| Horizon monitoring | ✅ | ✅ | ✅ | ✅ |
| Queue depth + failed jobs | ✅ | ✅ | ✅ | ✅ |
| Cron job tracking | ✅ | ✅ | ✅ | ✅ |
| Blade dashboard | ✅ | ✅ | ✅ | ✅ |
| History retention | Unlimited (own DB) | 7 days | 90 days | 1 year |
| Slack / PagerDuty alerts | ✅ | — | ✅ | ✅ |
| Multiple apps | Unlimited | 1 | 5 | Unlimited |
| Team members | N/A | 1 | 3 | Unlimited |
| REST API | ✅ | — | ✅ | ✅ |
| Status page | — | — | ✅ | ✅ |
| Priority support | — | — | — | ✅ |

---

## Integration model (how user's app talks to SaaS)

**Agent-based (pull + push hybrid):**

1. User installs the OSS package in their Laravel app
2. Package sends heartbeats + events to `app.crontinel.com` API via their `api_key`
3. SaaS stores and visualizes the data
4. No polling required from SaaS side — app pushes on events (job failed, cron ran, etc.)
5. Fallback: SaaS pings a `/health` endpoint on the user's app every N minutes (optional)

This means the OSS package serves double duty: standalone self-hosted dashboard AND the agent for the SaaS.

---

## Build order (sequencing)

1. **Landing page** (`crontinel.com`) — coming soon + email capture — 1 day
2. **OSS package MVP** — Horizon + Queue + Cron monitors, Blade dashboard, alerts — 2–3 weeks
3. **SaaS auth + teams** — Breeze, team creation, app management — 1 week
4. **SaaS agent integration** — package pushes to SaaS API — 1 week
5. **Billing** — Cashier + Stripe, Free/Pro/Team plans — 1 week
6. **Polish + launch** — status page, docs, Packagist submit — 1 week

**Total: ~6–8 weeks to full launch**

---

## CI/CD

| Component | Choice | Notes |
|---|---|---|
| Pipeline | GitHub Actions | Triggers on push to `main` and `staging` branches |
| Deploy | AWS CodeDeploy | Preferred when on AWS; fallback: SSH + rsync on Hetzner |
| Environments | `main` → production, `staging` → staging | PRs merge to `staging` first, then promote to `main` |
| Tests | Pest runs on every PR | Must pass before merge |

**Deploy flow:** Push to `staging` branch → GH Actions → run tests → CodeDeploy to `staging.crontinel.com`. Merge to `main` → GH Actions → run tests → CodeDeploy to `app.crontinel.com`.

---

## GDPR & Privacy

Full GDPR compliance required at launch.

| Requirement | Implementation |
|---|---|
| Cookie consent | Cookie consent banner (JS, opt-in for analytics cookies) |
| Privacy Policy | `/legal/privacy` on landing page (required at launch) |
| Terms of Service | `/legal/terms` on landing page (required at launch) |
| Data export | User can export all their data (JSON) from `/profile` |
| Data deletion | User can delete account + all data from `/profile` |
| Right to be forgotten | Account deletion purges all PII within 30 days |
| Data location | EU region preferred (Hetzner EU or AWS eu-central-1) |

---

## Analytics & Monitoring

| Tool | Purpose |
|---|---|
| Google Analytics 4 | Landing page visitor analytics |
| Gatus | Uptime monitoring for `app.crontinel.com`, API endpoints |
| Laravel Horizon | Queue worker monitoring (dogfooding) |

Analytics only on `crontinel.com` (landing). No tracking scripts on `app.crontinel.com` dashboard (privacy-friendly for paying users).

---

## Auth & Security

| Feature | Choice |
|---|---|
| Auth | Laravel Breeze (email/password) + GitHub OAuth (Socialite) |
| 2FA | Optional TOTP (not required at login) — user enables from profile |
| 2FA implementation | Built-in or `pragmarx/google2fa-laravel` |
| Session | Standard Laravel sessions (Redis-backed) |
| API auth | Bearer token (api_key for package, Sanctum PAT for REST API) |

---

## Decisions log

| Decision | Choice | Date |
|---|---|---|
| Product model | OSS package + hosted SaaS | 2026-04-05 |
| Pricing tiers | Free / Pro $19 / Team $49 | 2026-04-05 |
| Annual discount | 20% off (2 months free) | 2026-04-05 |
| Landing page stack | Astro hybrid on CF Pages free | 2026-04-05 |
| Docs site | Astro Starlight on CF Pages | 2026-04-05 |
| Status page | Gatus on Hetzner VPS | 2026-04-05 |
| Database | PostgreSQL always (RDS/Aurora on AWS, Neon/Supabase on Hetzner) | 2026-04-05 |
| Queue driver | Redis (start), SQS migration path available later | 2026-04-05 |
| Infra | AWS EC2 (if credits) or Hetzner VPS — DevOps handled by founder | 2026-04-05 |
| OSS license | MIT | 2026-04-05 |
| Billing | Laravel Cashier + Stripe | 2026-04-05 |
| Teams model | Multi-user + multi-app per team | 2026-04-05 |
| Repos | crontinel (public OSS) + crontinel-app (private SaaS) | 2026-04-05 |
| CI/CD | GitHub Actions + AWS CodeDeploy | 2026-04-05 |
| Staging | staging.crontinel.com (mirrors production stack) | 2026-04-05 |
| GDPR | Full compliance — cookie consent, data export, deletion | 2026-04-05 |
| Analytics | Google Analytics 4 on landing page only | 2026-04-05 |
| 2FA | Optional TOTP, recommended not required | 2026-04-05 |
| GitHub OAuth | Confirmed YES via Laravel Socialite | 2026-04-05 |

## Still open (non-blocking)

- [ ] Email provider for >3K/mo (Resend paid vs Postmark vs SES)
- [ ] Final infra choice (AWS credits pending approval — fallback Hetzner)
- [ ] Landing page repo visibility (public vs private)
- [ ] ploy.cloud CF Pages memory fix — needs CF account access
