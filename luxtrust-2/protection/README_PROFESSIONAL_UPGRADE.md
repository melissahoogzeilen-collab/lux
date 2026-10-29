# Professional Safe Anti-Bot Upgrade

This upgrade keeps the existing CAPTCHA/math CAPTCHA files and flow untouched. It adds a server-side layer around the current protection order instead of rewriting the existing checks.

## Changed / Added Files

### Updated
- `check.php`
  - Includes `protection_upgrade.php`.
  - Loads settings through a short safe cache when possible.
  - Uses trusted proxy logic for client IP when the new origin lock is enabled.
  - Adds the professional layer after whitelist and before heavy checks.
  - Adds IPv4 + IPv6 CIDR support to `ip_in_range()`.
  - Uses cached rDNS/country lookups when enabled.

- `admin/dashboard.php`
  - Adds dashboard toggles using the same `.feature-toggle` and `.toggle-switch` structure.
  - Adds CSRF token to same-page dashboard POST actions.
  - Adds feature-name whitelist before updating settings.
  - Keeps CAPTCHA toggles mutually exclusive: enabling normal CAPTCHA disables math CAPTCHA and enabling math CAPTCHA disables normal CAPTCHA.

- `admin/toggle_feature.php`
  - Adds feature-name whitelist.
  - Keeps CAPTCHA toggles mutually exclusive.

### Added
- `protection_upgrade.php`
  - Origin lock helper.
  - Adaptive risk engine.
  - Temporary TTL block helper.
  - Route-aware rate limits.
  - Cloudflare edge signal reader.
  - rDNS/country cache helper.
  - IPv4/IPv6 CIDR matcher.

- `admin/allowed_features.php`
  - Central whitelist for dashboard feature toggles.

- `admin/trusted_proxy_ranges.txt`
  - Trusted reverse proxy CIDR list. Default contains Cloudflare edge ranges.

- `sql/protection_professional_upgrade.sql`
  - Adds `temporary_blocks`, `protection_cache`, and `risk_events`.
  - Adds default settings rows for new toggles and thresholds.

## Install Steps

1. Backup current project files and database.
2. Upload/replace the updated files from this ZIP.
3. Import:

```sql
sql/protection_professional_upgrade.sql
```

4. Open admin dashboard.
5. Enable toggles gradually in this order:
   1. Fast Protection Cache
   2. Temporary Block TTL
   3. Route Rate Limits
   4. Adaptive Risk Engine
   5. Cloudflare Edge Signals only after you forward trusted Cloudflare headers to origin
   6. Origin Lock Mode only after confirming your origin is only reached through Cloudflare/trusted proxy

## New Dashboard Toggles

- Origin Lock Mode
- Adaptive Risk Engine
- Temporary Block TTL
- Route Rate Limits
- Cloudflare Edge Signals
- Fast Protection Cache

## Safe Defaults

The SQL keeps higher-risk toggles OFF by default to avoid breaking legitimate traffic:

- `origin_lock_enabled = 0`
- `adaptive_risk_engine_enabled = 0`
- `route_rate_limits_enabled = 0`
- `cloudflare_edge_scoring_enabled = 0`

It enables safe helper defaults:

- `temporary_block_ttl_enabled = 1`
- `advanced_cache_enabled = 1`

## Important Notes

- Do not enable Origin Lock until you confirm your origin server is not accessed directly except by trusted proxy IPs.
- Do not enable Cloudflare Edge Signals unless Cloudflare is forwarding bot score headers to your origin and your origin is locked. Otherwise any visitor could spoof those headers.
- Existing CAPTCHA and math CAPTCHA files were not modified.
- Existing bot checks remain in their current order except for the new safe layer inserted after whitelist and before heavy checks.
