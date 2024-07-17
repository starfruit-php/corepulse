# API

## Setup

Add some config to `config/packages/security.yaml`

```bash
security:
    ...
    firewalls:
        corepulse_cms_api: '%corepulse_admin.api_firewall_settings%'
    ...

    access_control:
        ...
        - { path: ^/corepulse/cms/api/auth, roles: PUBLIC_ACCESS }
        - { path: ^/corepulse/cms/api, roles: ROLE_COREPULSE_USER }
```

## Document
Updating
