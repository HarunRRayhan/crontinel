# Status Pages — Starter Code

> Full spec: `../docs/status-pages-spec.md`

## Files Created

### Models
- `../app/app/Models/StatusPage.php` — Team-owned status page with slug auto-gen
- `../app/app/Models/StatusPageEndpoint.php` — Endpoint with `evaluateStatus()` helper

### Controllers
- `../app/app/Http/Controllers/StatusPageController.php` — CRUD + endpoint management
- `../app/app/Http/Controllers/StatusPageEndpointController.php` — Add/remove endpoints
- `../app/app/Http/Controllers/StatusPageHealthController.php` — Public page (HTML + JSON API)

### Migrations
- `../app/database/migrations/2026_04_15_000000_create_status_pages_table.php`
- `../app/database/migrations/2026_04_15_000001_create_status_page_endpoints_table.php`

### Command
- `../app/app/Console/Commands/CheckStatusPages.php` — Artisan scheduler task

### Service
- `../app/app/Services/PlanLimits.php` — Extended with status page limits

### Routes
- Updated `../app/routes/web.php` — added all status page routes
- Updated `../app/routes/console.php` — added `status-pages:check` scheduler entry

### Views
- `../app/resources/views/status-pages/index.blade.php`
- `../app/resources/views/status-pages/create.blade.php`
- `../app/resources/views/status-pages/edit.blade.php`
- `../app/resources/views/status-pages/endpoints.blade.php`
- `../app/resources/views/status/show.blade.php` — public status page (dark theme)

## Routes Summary

| Method | URI | Auth | Action |
|--------|-----|------|--------|
| GET | `/status-pages` | team | `index` |
| GET | `/status-pages/create` | team | `create` |
| POST | `/status-pages` | team | `store` |
| GET | `/status-pages/{id}/edit` | team | `edit` |
| PATCH | `/status-pages/{id}` | team | `update` |
| DELETE | `/status-pages/{id}` | team | `destroy` |
| GET | `/status-pages/{id}/endpoints` | team | `endpoints` |
| POST | `/status-pages/{id}/endpoints` | team | `store` (endpoint) |
| DELETE | `/status-pages/{id}/endpoints/{ep}` | team | `destroy` (endpoint) |
| GET | `/status/{slug}` | public | `view` (HTML) |
| GET | `/api/status-pages/{slug}` | public | `show` (JSON) |

## To Deploy

```bash
# 1. Run migrations
cd ~/Work/crontinel/app
php artisan migrate

# 2. Test the command
php artisan status-pages:check

# 3. Commit and push
git add -A && git commit -m "feat: status pages feature stub" && git push
```
