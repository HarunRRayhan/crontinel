# Crontinel — Computer Access Required Checklist

These are the things that **require direct computer access or manual account work** before the AI agent can proceed autonomously. Work through these items in order — once done, Claude Code can build the rest.

---

## Phase 0 — Before any code is deployed

### DNS & Cloudflare
- [ ] Add `crontinel.com` to Cloudflare account
- [ ] Set nameservers at registrar to point to Cloudflare
- [ ] Create DNS records:
  - `crontinel.com` → CF Pages (landing page)
  - `app.crontinel.com` → server IP (SaaS)
  - `staging.crontinel.com` → staging server IP
  - `docs.crontinel.com` → CF Pages (docs)
  - `status.crontinel.com` → server IP (Gatus)
- [ ] Enable "Always Use HTTPS" in Cloudflare

### Cloudflare Pages
- [ ] Create CF Pages project for `crontinel.com` (landing)
- [ ] Create CF Pages project for `docs.crontinel.com` (docs)
- [ ] Connect both to GitHub repo

### GitHub
- [ ] Create private repo: `github.com/HarunRRayhan/crontinel-app` (SaaS app)
- [ ] Create repo: `github.com/HarunRRayhan/crontinel-landing` (or keep in crontinel monorepo TBD)
- [ ] Add GitHub Actions secrets:
  - `AWS_ACCESS_KEY_ID` + `AWS_SECRET_ACCESS_KEY` (deploy user)
  - `APP_KEY` (Laravel production key)
  - `DB_PASSWORD`
  - `STRIPE_SECRET` + `STRIPE_WEBHOOK_SECRET`
  - `RESEND_API_KEY`
  - `CRONTINEL_SLACK_WEBHOOK` (alerts for our own app)
- [ ] Add staging-specific secrets (same names with `_STAGING` suffix, or use GitHub Environments)

---

## Phase 1 — Infrastructure (AWS path)

### AWS setup (if credits approved)
- [ ] Apply for AWS Activate credits (startup program)
- [ ] Create EC2 instance:
  - Region: `eu-central-1` (EU for GDPR)
  - Type: `t3.small` (start) or `t3.medium`
  - OS: Ubuntu 24.04 LTS
  - Security groups: ports 22, 80, 443 open
- [ ] Create RDS PostgreSQL instance (or Aurora PostgreSQL Serverless)
  - Same VPC as EC2
  - Store DB password in AWS Secrets Manager
- [ ] Create ElastiCache Redis cluster (cache.t3.micro to start)
- [ ] Set up IAM user for GitHub Actions deploy (CodeDeploy policy)
- [ ] Install CodeDeploy agent on EC2
- [ ] Configure Application Load Balancer (for HTTPS via ACM)
- [ ] Request ACM certificate for `app.crontinel.com` and `staging.crontinel.com`
- [ ] Configure second EC2 + RDS instance for staging (or use separate DB within same RDS)

### Server setup (manual, once)
```bash
# Run on EC2 after provisioning
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.3-fpm php8.3-pgsql php8.3-redis php8.3-zip php8.3-mbstring php8.3-xml php8.3-curl
sudo apt install -y redis-server
curl -sLS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## Phase 1 — Infrastructure (Hetzner fallback)

### Hetzner setup
- [ ] Create CX22 VPS (2 vCPU, 4GB, Ubuntu 24.04)
  - Location: Falkenstein or Helsinki (EU for GDPR)
- [ ] Set up SSH key authentication (disable password auth)
- [ ] Install same stack as AWS path above
- [ ] Set up Neon account: neon.tech → create project → get connection string
- [ ] Configure Let's Encrypt (certbot) for SSL

---

## Phase 2 — Third-party services

### Stripe
- [ ] Create Stripe account (or use existing)
- [ ] Create products in Stripe dashboard:
  - `prod_crontinel_pro` → monthly + annual prices
  - `prod_crontinel_team` → monthly + annual prices
- [ ] Note all Price IDs → add to `.env` as:
  - `STRIPE_PRO_MONTHLY_PRICE_ID`
  - `STRIPE_PRO_ANNUAL_PRICE_ID`
  - `STRIPE_TEAM_MONTHLY_PRICE_ID`
  - `STRIPE_TEAM_ANNUAL_PRICE_ID`
- [ ] Set up Stripe webhook → `app.crontinel.com/stripe/webhook`
  - Events: `checkout.session.completed`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.payment_failed`, `invoice.paid`
- [ ] Set `STRIPE_WEBHOOK_SECRET` from Stripe dashboard

### Resend
- [ ] Create Resend account
- [ ] Verify `crontinel.com` domain (DKIM, SPF records in Cloudflare)
- [ ] Create API key → `RESEND_API_KEY`
- [ ] Create audience for email capture (landing page waitlist)
- [ ] Set up `alerts@crontinel.com` and `support@crontinel.com` as sending identities

### Google Analytics
- [ ] Create GA4 property for `crontinel.com`
- [ ] Get Measurement ID (`G-XXXXXXXXXX`) → add to landing page config
- [ ] Configure cookie consent to only load GA after opt-in

---

## Phase 3 — Before Milestone 6 (Launch)

### Packagist
- [ ] Create Packagist account (or use existing)
- [ ] Submit `harunrrayhan/crontinel` package
- [ ] Set up GitHub webhook for auto-update on push

### npm (for MCP server)
- [ ] Create npm account (or use existing)
- [ ] Reserve `@crontinel/mcp-server` package name

### Social / community
- [ ] Create `@crontinel` on Twitter/X
- [ ] Create GitHub Discussions on the OSS repo
- [ ] Create `crontinel` on Reddit (r/crontinel if needed) — or just post in r/laravel
- [ ] Product Hunt: schedule launch, create account/draft

### MCP registries (submit after package is published)
- [ ] Submit to Smithery
- [ ] Submit to Glama
- [ ] Submit to mcp.so

---

## Notes

- Most of the above is **one-time setup** — once done, all deploys and code changes happen automatically via GitHub Actions.
- The AI agent (Claude Code) handles everything after initial infrastructure is up: writing code, running tests, deploying via CI.
- Staging is your safety net — always verify on `staging.crontinel.com` before production.
