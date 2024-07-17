# CorePulse CMS

## Installation

1. On your Pimcore 11 root project:

```bash
composer require corepulse/corepulse
```

2. Update `config/bundles.php` file:

```bash
return [
    ....
    ValidatorBundle\ValidatorBundle::class => ['all' => true],
    Rompetomp\InertiaBundle\RompetompInertiaBundle::class => ['all' => true],
    Pentatrion\ViteBundle\PentatrionViteBundle::class => ['all' => true],
    CorepulseBundle\CorepulseBundle::class => ['all' => true],
];
```

3. Install bundle:

```bash
    ./bin/console pimcore:bundle:install CorepulseBundle
```

4. Update `config/packages/security.yaml` file:

```bash
security:
    ...
    firewalls:
        corepulse_cms: '%corepulse_admin.firewall_settings%'
    ...

    access_control:
        ...
        - { path: ^/cms/login, roles: PUBLIC_ACCESS }
        - { path: ^/cms, roles: ROLE_COREPULSE_USER }
```

5. Setup default in Pimcore admin UI first then enjoy with https://your-domain/cms

![Setup default in Pimcore admin UI](/docs/img/setup-first.png "Setup default in Pimcore admin UI")

## Update
Run command to create or update custom database configs:

```bash
    # create tables
    ./bin/console corepulse:setup
    # update with option `--update` or `-u`
    ./bin/console corepulse:setup -u
```

## API
[See more](docs/API.md)


## Document
Full documents [here](docs)
