# vuetify bundle

## add config/bundles.php

```
ValidatorBundle\ValidatorBundle::class => ['all' => true],
Rompetomp\InertiaBundle\RompetompInertiaBundle::class => ['all' => true],
Pentatrion\ViteBundle\PentatrionViteBundle::class => ['all' => true],
CorepulseBundle\CorepulseBundle::class => ['all' => true],
```

## bundle install

```
 ./bin/console pimcore:bundle:install CorepulseBundle
```

## add firewall

```

security:
	firewalls:
		corepulse_cms: '%corepulse_admin.firewall_settings%' 
	        
```

```
security:
	access_control:
		- { path: ^/cms/login, roles: PUBLIC_ACCESS }
		- { path: ^/cms, roles: ROLE_COREPULSE_USER }
```
