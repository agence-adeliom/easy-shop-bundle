
![Adeliom](https://adeliom.com/public/uploads/2017/09/Adeliom_logo.png)

# Easy Shop Bundle

Implentation of Sylius into Easyadmin

## Installation

Install with composer

```bash
composer require agence-adeliom/easy-shop-bundle

bin/console sylius:install:check-requirements
```

## Documentation

### Setup `security.yaml`

#### Encoders

```yaml
security:
    encoders:
        ...
        Sylius\Component\User\Model\UserInterface: argon2i
```

#### Providers

```yaml
security:
    ...
    providers:
        ...
        sylius_api_admin_user_provider:
            id: sylius.admin_user_provider.email_or_name_based

        sylius_shop_user_provider:
            id: sylius.shop_user_provider.email_or_name_based

        sylius_api_shop_user_provider:
            id: sylius.shop_user_provider.email_or_name_based
```

#### Firewalls

```yaml
security:
    ...
    access_control:
        ...
        new_api_admin_user:
            pattern: "%sylius.security.new_api_admin_regex%/.*"
            provider: sylius_api_admin_user_provider
            stateless: true
            json_login:
                check_path: "%sylius.security.new_api_admin_route%/authentication-token"
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator

        new_api_shop_user:
            pattern: "%sylius.security.new_api_shop_regex%/.*"
            provider: sylius_api_shop_user_provider
            stateless: true
            json_login:
                check_path: "%sylius.security.new_api_shop_route%/authentication-token"
                username_path: email
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator


        shop:
            switch_user: { role: ROLE_ALLOWED_TO_SWITCH }
            context: shop
            pattern: "%sylius.security.shop_regex%"
            provider: sylius_shop_user_provider
            form_login:
                success_handler: sylius.authentication.success_handler
                failure_handler: sylius.authentication.failure_handler
                provider: sylius_shop_user_provider
                login_path: sylius_shop_login
                check_path: sylius_shop_login_check
                failure_path: sylius_shop_login
                default_target_path: sylius_shop_homepage
                use_forward: false
                use_referer: true
            remember_me:
                secret: "%env(APP_SECRET)%"
                name: APP_SHOP_REMEMBER_ME
                lifetime: 31536000
                remember_me_parameter: _remember_me
            logout:
                path: sylius_shop_logout
                target: sylius_shop_login
                invalidate_session: false
```

#### Access controls

```yaml
security:
    ...
    access_control:
        ...
        - { path: "%sylius.security.shop_regex%", role: PUBLIC_ACCESS }
        - { path: "%sylius.security.shop_regex%/_partial", role: IS_AUTHENTICATED_ANONYMOUSLY, ips: [ 127.0.0.1, ::1 ] }
        - { path: "%sylius.security.shop_regex%/_partial", role: ROLE_NO_ACCESS }
        - { path: "%sylius.security.shop_regex%/login", role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: "%sylius.security.shop_regex%/register", role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: "%sylius.security.shop_regex%/verify", role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: "%sylius.security.shop_regex%/account", role: ROLE_USER }

        - { path: "%sylius.security.new_api_admin_regex%/.*", role: ROLE_API_ACCESS }
        - { path: "%sylius.security.new_api_admin_route%/authentication-token", role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: "%sylius.security.new_api_user_account_regex%/.*", role: ROLE_USER }
        - { path: "%sylius.security.new_api_shop_route%/authentication-token", role: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: "%sylius.security.new_api_shop_regex%/.*", role: IS_AUTHENTICATED_ANONYMOUSLY }

        - { path: ^/, role: PUBLIC_ACCESS }
```

#### Setup Sylius

```bash
bin/console sylius:install:setup
```

## License

[MIT](https://choosealicense.com/licenses/mit/)


## Authors

- [@arnaud-ritti](https://github.com/arnaud-ritti)
- [@JeromeEngelnAdeliom](https://github.com/JeromeEngelnAdeliom)


