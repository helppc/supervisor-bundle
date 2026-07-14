# HelpPC Supervisor Bundle

[![CI](https://github.com/helppc/supervisor-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/helppc/supervisor-bundle/actions/workflows/ci.yml)
[![License](https://poser.pugx.org/helppc/supervisor-bundle/license)](https://packagist.org/packages/helppc/supervisor-bundle)

Symfony bundle for managing [Supervisor](http://supervisord.org/) processes over
its XML-RPC API — list processes, start/stop/restart them and tail their logs
from a small web UI. Implemented on top of the
[supervisorphp/supervisor](https://github.com/supervisorphp/supervisor) library.

## Requirements

* PHP 8.2 or greater
* Symfony 6.4, 7.x or 8.x

Upgrading from 1.x? See [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Installation

1. Require the bundle with Composer:

    ```sh
    composer require helppc/supervisor-bundle
    ```

    The bundle ships its own PSR-17/PSR-18 stack (`nyholm/psr7` +
    `symfony/http-client`) — no extra wiring is needed.

1. Enable the bundle in `config/bundles.php`:

    ```php
    HelpPC\Bundle\SupervisorBundle\SupervisorBundle::class => ['all' => true],
    ```

1. Create `config/packages/helppc_supervisor.yaml`. Full reference:

    ```yaml
    helppc_supervisor:
        default_environment: prod
        servers:
            prod:
                localhost:
                    scheme: http            # default
                    host: 127.0.0.1         # required
                    port: 9001              # default
                    username: '%env(SUPERVISOR_RPC_USERNAME)%'  # optional — HTTP basic auth
                    password: '%env(SUPERVISOR_RPC_PASSWORD)%'  # optional
                    hidden_processes: []    # process/group names that must never
                                            # be listed or controlled (see below)
                    log_tail_bytes: 16384   # how much of a log tail to render
    ```

1. Import the routes in `config/routes/helppc_supervisor.yaml`:

    ```yaml
    helppc_supervisor:
        resource: '@SupervisorBundle/config/routes.php'
        prefix: /supervisor
    ```

1. Restrict access — the bundle does no authorization by itself. All
   state-changing routes are `POST`-only, but you still want an
   `access_control` rule (or your own means) limiting who can reach the UI:

    ```yaml
    security:
        access_control:
            - { path: ^/supervisor, roles: ROLE_ADMIN }
    ```

## Supervisord configuration

The bundle talks to supervisord's HTTP XML-RPC endpoint. Enable it in
`supervisord.conf`, ideally bound to loopback and protected by credentials
(supervisord expands real environment variables via `%(ENV_...)s`):

```ini
[inet_http_server]
port=127.0.0.1:9001
username=%(ENV_SUPERVISOR_RPC_USERNAME)s
password=%(ENV_SUPERVISOR_RPC_PASSWORD)s

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface
```

Note on timeouts: `stopProcess`/`restartProcess` wait for the process to stop,
which can take up to the program's `stopwaitsecs`. Make sure your PHP HTTP
client timeout (default 60 s with `symfony/http-client`) exceeds the largest
`stopwaitsecs` you use.

## Hidden processes

`hidden_processes` is a per-server deny-list of process **names or group
names**. A hidden process:

* never appears in the process list, and
* cannot be targeted at all — start/stop/restart/log routes return **404**
  before any RPC call is made.

Matching the group as well covers `numprocs > 1` programs (process `worker_00`
in group `worker`) and event listeners (whose group equals their name). Typical
use: hide the processes that keep the container itself alive:

```yaml
hidden_processes: [php-fpm, nginx, fatal_exit]
```

Anything you later add to `supervisord.conf` becomes manageable automatically —
only processes that must stay untouchable need to be listed here.

## Templates

Every page renders inside the `@Supervisor/layout.html.twig` layout. To embed
the UI into your application chrome, override that single file
(`templates/bundles/SupervisorBundle/layout.html.twig`):

```twig
{% extends 'base.html.twig' %}
{% block body %}{% block supervisor_content %}{% endblock %}{% endblock %}
```

Translations ship in English and Czech (domain `SupervisorBundle`).

## Reporting issues

Use the [issue tracker](https://github.com/helppc/supervisor-bundle/issues) to report any issues you might have.

## License

See the [LICENSE](LICENSE.md) file for license rights and limitations (MIT).
