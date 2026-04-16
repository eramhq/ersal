# Contributing to Ersal

Thank you for your interest in contributing to Ersal!

## Adding a New Provider

1. Create a directory under `src/Provider/YourProvider/`
2. Create three files:
   - `YourProviderConfig.php` — readonly DTO with provider credentials
   - `YourProviderProvider.php` — extends `AbstractProvider` (REST) or `AbstractSoapProvider` (SOAP)
   - `YourProviderErrorCode.php` — enum with bilingual error messages
3. Register the provider alias in `src/Ersal.php`
4. Add unit tests in `tests/Unit/Provider/YourProviderProviderTest.php`
5. Update the provider table in `README.md`

## Development Setup

```bash
git clone https://github.com/eramhq/ersal.git
cd ersal
composer install
```

## Running Tests

```bash
vendor/bin/phpunit
```

## Code Quality

```bash
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix
```

## Pull Request Guidelines

- One feature/fix per PR
- All tests must pass
- PHPStan level 6 must pass
- Follow PER-CS2 coding style
- Add tests for new providers
- Error code enums must have both Persian and English messages
