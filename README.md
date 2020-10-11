# HelpPC Supervisor Bundle

[![Build Status](https://gitlab.com/helppc/supervisor-bundle/badges/master/pipeline.svg)](https://gitlab.com/helppc/supervisor-bundle)
[![License](https://poser.pugx.org/trikoder/oauth2-bundle/license)](https://packagist.org/packages/helppc/supervisor-bundle)

Symfony bundle which manage supervisor process. Bundle is implemented using the [supervisorphp/supervisor](https://github.com/supervisorphp/supervisor) library.

## Status

This package is currently in the active development.


## Requirements

* [PHP 7.4](http://php.net/releases/7_4_0.php) or greater
* [Symfony 5.x](https://symfony.com/roadmap/5.0)

## Installation

1. Require the bundle and a PSR 7/17 implementation with Composer:

    ```sh
    composer require helppc/supervisor-bundle nyholm/psr7
    ```

    > **NOTE:** This bundle requires a PSR 7/17 implementation to operate. We recommend that you use [nyholm/psr7](https://github.com/Nyholm/psr7). Check out this [document](docs/psr-implementation-switching.md) if you wish to use a different implementation.

1. Create the bundle configuration file under `config/packages/helppc_supervisor.yaml`. Here is a reference configuration file:

    ```yaml
    supervisor:
      default_environment: all
      servers:
        all:
          localhost:
            scheme: http
            host: 127.0.0.1
            port: 9006
    ```

1. Enable the bundle in `config/bundles.php` by adding it to the array:

    ```php
    HelpPC\Bundle\SupervisorBundle\SupervisorBundle::class => ['all' => true]
    ```

1. Import the routes inside your `config/routes/helppc_supervisor.yaml` file:

    ```yaml
    supervisor:
      resource: "@SupervisorBundle/Resources/config/routing.xml"
      prefix:   /supervisor
    ```

**❮ NOTE ❯** It is recommended to control the access to the authorization endpoint
so that only logged in users can approve authorization requests.
You should review your `security.yml` file. Here is a sample configuration:

```yaml
security:
    access_control:
        - { path: ^/supervisor, roles: IS_AUTHENTICATED_REMEMBERED }
```

## Reporting issues

Use the [issue tracker](https://gitlab.com/helppc/supervisor-bundle/-/issues) to report any issues you might have.

## License

See the [LICENSE](LICENSE.md) file for license rights and limitations (MIT).
