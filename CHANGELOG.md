# Changelog

All notable changes to `laravel-strapi` will be documented in this file.

## 1.0.0 - 2026-03-22

### First public release

This is the initial release of `combindma/laravel-strapi`, a Laravel package for consuming a Strapi v5 GraphQL API through a single, Laravel-friendly service.

#### Highlights

- GraphQL client integration for Strapi v5
- Singleton service registration with a convenient `Strapi` facade
- Typed readonly DTOs for structured content
- Built-in Laravel cache support for all content accessors
- Webhook endpoint to invalidate cached content after Strapi updates
- Low-level `query()` access for custom GraphQL queries when needed

#### Included content accessors

This first release includes ready-to-use methods for common Strapi content shapes:

- SEO data
- Page content with hero and sections
- Social network links
- Privacy content
- Contact information
- Services listing
- Service detail pages

#### Developer experience

- Simple config-based setup using `STRAPI_GRAPHQL_URL`, `STRAPI_TOKEN`, `STRAPI_TIMEOUT`, and `STRAPI_WEBHOOK_SECRET`
- Published config file for easy customization
- Support for responsive image data through dedicated DTOs
- Cache clearing helpers for page, service, and singleton content
- Built to fit naturally into Laravel applications without scattering GraphQL logic across the codebase

#### Compatibility

- PHP `^8.4`
- Laravel `^12.0 | ^13.0`

#### Quality

The package ships with automated test coverage for service registration, GraphQL querying, caching behavior, DTO mapping, webhook validation, and cache invalidation.

Thanks for checking out the first release of `laravel-strapi`. Feedback, issues, and contributions are welcome.
