# Unstoppable Voting, Polls & Elections

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/unstoppable-voting-polls-elections.svg)](https://wordpress.org/plugins/unstoppable-voting-polls-elections/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/unstoppable-voting-polls-elections.svg)](https://wordpress.org/plugins/unstoppable-voting-polls-elections/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

Secure polls and formal elections for WordPress — board votes, officer elections, member surveys, tournament brackets, captain voting, and more. Five ballot templates, seven sort modes, and powerful duplicate-vote prevention. Built for organizations that take voting seriously.

> **End users:** install from the WordPress Plugin Directory — [wordpress.org/plugins/unstoppable-voting-polls-elections](https://wordpress.org/plugins/unstoppable-voting-polls-elections/)
>
> **This repository is for development.** Browse the code, follow releases, file issues, submit pull requests.

## Features

- **Five frontend templates** — Classic Bars, Official Ballot, Candidate Cards, Compact List, Tournament
- **Seven option sort orders** — manual, A→Z, Z→A, most votes, least votes, random, date added
- **Four duplicate-vote prevention modes** — cookie, IP, both, or none; per-poll
- **Single or multiple choice** with configurable max selections
- **Optional scheduling** with start / end dates
- **Four result-display modes** — before vote, after vote, when closed, admin-only
- **Guest voting toggle** per poll
- **Shortcode-driven** — drop `[uvpe_poll id="N"]` anywhere
- **Zero outbound HTTP** — nothing phones home

## Who it's for

Non-profits, HOAs, schools, churches, clubs, sports leagues, businesses — anyone who runs real votes on a WordPress site and wants a clean, free, no-upsell tool.

## Companion Pro add-on

A separate commercial add-on (`unstoppable-voting-polls-elections-pro`, maintained in its own private repository) extends this plugin with private polls, role-restricted voting, email notifications on every vote, custom branded receipts, and multi-site vote aggregation. The Pro add-on requires this free plugin to be installed and active.

## Development

### Repository layout

```
.
├── admin/            Admin-area view templates
├── assets/           Frontend + admin CSS and JS
├── includes/         Core PHP classes (UVPE_* prefix)
├── languages/        Translation .pot location
├── templates/        Frontend poll templates (one per ballot style)
├── readme.txt        WordPress.org readme (standard WP format)
└── unstoppable-voting-polls-elections.php   Main plugin bootstrap
```

### Release workflow

Releases are deployed to WordPress.org automatically by GitHub Actions:

1. Finish the feature work on `main`
2. Bump the version in **all four** required locations:
   - `unstoppable-voting-polls-elections.php` — `Version:` header
   - `unstoppable-voting-polls-elections.php` — `UVPE_VERSION` constant
   - `readme.txt` — `Stable tag:`
   - `readme.txt` — add a `== Changelog ==` entry
3. Commit and push `main`
4. On GitHub, create a new **Release** tagged `vX.Y.Z` (e.g. `v2.1.0`)
5. The `deploy-to-wporg.yml` workflow fires and pushes the release to WordPress.org SVN automatically

### CI

Every push and pull request runs the official WordPress Plugin Check against the codebase.

## License

GPL-2.0-or-later. See the `License` header in `unstoppable-voting-polls-elections.php` for the full text.

## Author

Developed and maintained by [Unstoppable Solutions](https://unstoppablesolutions.app) — Dave Stevens / `paramedic192`.
