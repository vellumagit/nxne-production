# NXNE 2026 Production Dashboard

Internal production management dashboard for NXNE 2026 (June 10-14, Toronto).

## Live Data

Polls a Google Sheets DASHBOARD_CACHE tab every 30 seconds via published JSON URL.

## AI Analysis

AI analysis buttons route through a Make.com webhook → Anthropic Claude API.

## Updating

Any push to main auto-deploys via GitHub Pages. `git push` and it's live within ~60 seconds.

## Setup

See DASHBOARD_CACHE_URL and MAKE_WEBHOOK_URL fields in the dashboard config bar.
