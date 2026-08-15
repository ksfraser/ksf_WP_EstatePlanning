# KSF WP Estate Planning

WordPress plugin for estate planning with REST API, Gutenberg blocks, and shortcodes.

## Installation

1. Copy to `wp-content/plugins/ksf-estate-planning`
2. Activate in WordPress Admin → Plugins
3. Configure in Settings → Estate Planning

## Features

- **REST API**: `/wp-json/ksf-ep/v1/calculate-tax`
- **Shortcodes**: `[ksf_estate_calculator]`, `[ksf_estate_scenarios]`
- **Gutenberg Blocks**: Estate Calculator block
- **Admin UI**: Scenario management

## Requirements

- WordPress 5.8+
- PHP 7.3+
- ksfraser/ksf-estateplanning (via Composer)

## Development

```bash
composer install
npm install
npm run build
```

## Testing

```bash
./vendor/bin/phpunit
```