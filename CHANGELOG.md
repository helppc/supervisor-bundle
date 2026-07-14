# Changelog

## v2.0.0

Modernization + hardening release. See [UPGRADE-2.0.md](UPGRADE-2.0.md) for the
full migration guide.

### Changed

* PHP >= 8.2, Symfony 6.4 / 7.x / 8.x, `supervisorphp/supervisor` ^5.1.
* Replaced the abandoned php-http/HTTPlug + `symfony/templating` stack with
  PSR-17/PSR-18 (`fXmlRpc\Transport\PsrTransport`); the bundle registers its
  own `nyholm/psr7` factory and `symfony/http-client` PSR-18 client.
* Config root key `supervisor` → `helppc_supervisor`; parameter
  `supervisor.servers` → `helppc_supervisor.servers`; per-server `host` is
  required.
* Modern bundle layout (`config/`, `templates/`, `translations/`); services and
  routes are PHP configs (Symfony 8 removed the XML loaders).
* All state-changing actions are POST-only routes (`supervisor.process.start`,
  `supervisor.process.stop`, `supervisor.process.restart`).
* Process log views render a bounded tail (`log_tail_bytes`, default 16 KiB)
  instead of reading the entire log.
* Templates rewritten as minimal semantic HTML (no Bootstrap 3, jQuery or
  fancybox); override `layout.html.twig` to embed into an application layout.
* CI moved from GitLab CI to GitHub Actions (PHP 8.2–8.4, lowest/highest deps,
  PHPStan 2, PHPUnit 11).

### Added

* Per-server HTTP basic auth (`username`/`password`) via a PSR-18 decorator —
  credentials never appear in URLs or exception messages.
* Per-server `hidden_processes` deny-list (matches process name or group):
  hidden processes are excluded from every listing and hard-404 on all
  process routes before any RPC call.
* `restart` action (stop-and-wait, then start).
* Czech + English translations for the new actions.

### Removed

* Start-all/stop-all endpoints (`supervisor.processes.startStop`) — unsafe with
  `hidden_processes`; may return later as an explicit opt-in.
* Log clearing endpoints (`supervisor.log.clear`, `supervisor.process.log.clear`).
* AJAX process-info endpoints and the jQuery refresh machinery.
