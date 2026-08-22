# AGENTS.md

Guidance for AI agents working in this repository.

## What this repo is

`gvm-wp-plugin` — WordPress plugin integrating GetViaMsg (`gvm.js`) paywalls into WordPress sites.

## Structure

- `gvm-wp.php` — plugin bootstrap (header, constants, activation).
- `includes/` — one class per concern (`class-gvm-*.php`).
- `assets/` — editor JS/CSS for the Gutenberg block.
- `templates/` — paywall `<template>` snippets.
- `build.sh` — pulls `dist/gvm.js` from `gvm-sdk` into `assets/`.

## Conventions

- Plain PHP, no framework; follow WordPress coding standards (snake_case functions/hooks, `Gvm_`/`GvmWp_` class prefix, docblocks).
- Escape all output (`esc_attr`, `esc_html`, `esc_url`); use nonces + `current_user_can` on all inputs.
- Settings via the Settings API / `register_setting`.
- `gvm.js` is ESM -> enqueue as a module (`wp_enqueue_script_module`, WP 6.3+).
- Never commit secrets (tenant secret, signing key).

## Current work

See `PLAN.md`.
