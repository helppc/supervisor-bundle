# Upgrade from 1.x to 2.0

## Requirements

* PHP >= 8.2 (was ^7.4|^8)
* Symfony 6.4 / 7.x / 8.x (was 4.4 / 5.x)
* `supervisorphp/supervisor` ^5.1 (was ^4.0)
* The php-http/HTTPlug stack (`Http\Client\HttpClient`,
  `Http\Message\MessageFactory`) was replaced by PSR-17/PSR-18. The bundle now
  registers its own `nyholm/psr7` factory and `symfony/http-client` PSR-18
  client — an application no longer needs to provide any HTTP services.

## Configuration root key

The config root key changed from `supervisor` to `helppc_supervisor`:

```diff
-supervisor:
+helppc_supervisor:
     default_environment: prod
     servers: ...
```

Per-server `host` is now required; `port` defaults to `9001`. New optional
per-server keys: `username`, `password` (HTTP basic auth), `hidden_processes`,
`log_tail_bytes`.

## Routing

The routing resource moved and changed format (Symfony 8 dropped XML loaders):

```diff
 helppc_supervisor:
-    resource: '@SupervisorBundle/Resources/config/routing.xml'
+    resource: '@SupervisorBundle/config/routes.php'
     prefix: /supervisor
```

Route changes:

* `supervisor.process.startStop` (GET, `{start}` flag) was replaced by three
  **POST-only** routes: `supervisor.process.start`, `supervisor.process.stop`
  and the new `supervisor.process.restart`
  (`/{key}/process/{group}/{name}/(start|stop|restart)`).
* `supervisor.process.log` / `supervisor.process.error` moved to
  `/{key}/process/{group}/{name}/log/(stdout|stderr)` and now render a bounded
  tail (`log_tail_bytes`) instead of the whole log.
* **Removed** routes: `supervisor.processes.startStop` (start/stop-all — unsafe
  in combination with `hidden_processes`), `supervisor.processes.info` (AJAX
  refresh), `supervisor.log.clear`, `supervisor.process.log.clear`.

## Services

* The container parameter `supervisor.servers` was renamed to
  `helppc_supervisor.servers`.
* `SupervisorManager` is no longer `public`; inject it (the class-name id is
  autowirable) instead of fetching it from the container.
* `SupervisorManager::getSupervisors()` / `getSupervisorByKey()` are unchanged.
  New API: `isProcessVisible()`, `getVisibleProcesses()`, `getLogTailBytes()`.

## Templates

Templates live in `templates/` (still the `@Supervisor` namespace) and were
rewritten without Bootstrap 3 / jQuery / fancybox. Blocks: pages fill
`supervisor_content` inside `@Supervisor/layout.html.twig`. Applications that
overrode `Resources/views/*` must re-do their overrides under
`templates/bundles/SupervisorBundle/` against the new markup — usually
overriding only `layout.html.twig` is enough.

## Translations

Domain `SupervisorBundle` (cs, en) kept. Removed keys: `stop.all`, `start.all`,
`logs.delete`, `logs.delete.error`, `refresh`, `updating`, `loading`,
`starting`, `stopping`. New keys: `restart`, `no.processes`, `back`,
`process.start.success`, `process.stop.success`, `process.restart.success`,
`process.restart.error`.
