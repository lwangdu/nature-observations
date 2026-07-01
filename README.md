# Nature iNaturalist Observations

A small WordPress plugin for displaying live iNaturalist observations on Nature sites. The plugin pulls observations from the iNaturalist API, caches responses with WordPress transients, and renders a Gutenberg block with cards, stats, filters, and pagination.

## Features

- Dynamic `iNaturalist Observations` block.
- Dynamic `iNaturalist Observations Map` block.
- iNaturalist source options for project slug, project ID fallback, place ID, or user/account login.
- Observation cards with photo, common name, scientific name, observation date, observer, and quality grade.
- Filters for all observations, birds, mammals, plants, insects, and fungi.
- Stats cards for observations shown on the current page and all-time source totals.
- Pagination for larger projects, with 100 observations per page by default.
- Optional setting for opening iNaturalist links in a new tab.
- Admin cache clearing for refreshing stale iNaturalist data.
- Cached iNaturalist API requests to reduce page-load and API pressure.
- Legacy shortcode support via `[nature_inat_observations]`.
- Legacy map shortcode support via `[nature_inat_observations_map]`.

## Usage

1. Activate the plugin in WordPress.
2. Open or create an `iNaturalist Observations` page.
3. Add the `iNaturalist Observations` block.
4. Use the WordPress page title and a normal paragraph block for the page heading and intro text.
5. Configure the iNaturalist source in the block sidebar:
   - `Project slug` for an iNaturalist project slug.
   - `Project ID fallback` when a numeric project ID is needed.
   - `Place ID` for reserve boundary-based feeds.
   - `User ID or login` for account-based feeds.
   - `Observations per page` for pagination size.
   - `Open iNaturalist links in a new tab` for per-block link behavior.

The plugin creates draft starter `iNaturalist Observations` and `Map of Observations` pages on activation if they do not already exist. Review and publish those pages when ready.

## Map Page

Use the `iNaturalist Observations Map` block or `[nature_inat_observations_map]` shortcode to show a compact map of recent georeferenced observations with a small recent-observations photo strip underneath.

The starter map page uses the Stunt Ranch project and up to 200 mapped observations by default.

## Third-party Services

This plugin connects to third-party services to display observation and map data.

- iNaturalist API and observation media: sends configured source values such as project slug, project ID, place ID, user ID/login, page number, per-page count, and selected taxon filter to retrieve observations, stats, project data, place data, place boundary geometry, and observation photo URLs. Front-end pages may load observation images from the image URLs returned by iNaturalist, including iNaturalist/Open Data storage URLs. No WordPress user account data is intentionally sent by the plugin. API documentation: <https://api.inaturalist.org/v1/docs/>. Terms: <https://www.inaturalist.org/terms>. Privacy policy: <https://www.inaturalist.org/privacy>.
- Esri ArcGIS World Topographic Map tiles: the map view loads terrain/topographic map tiles from Esri ArcGIS. Browser requests to Esri include normal web request data such as IP address and user agent. Terms: <https://www.esri.com/en-us/legal/terms/full-master-agreement>. Privacy statement: <https://www.esri.com/en-us/privacy/overview>.

## Bundled Libraries

- Leaflet 1.9.4 is bundled locally in `assets/vendor/leaflet/` for the interactive map. Leaflet is licensed under the BSD 2-Clause License. The license is included at `assets/vendor/leaflet/LICENSE`.

## Settings

Global defaults are available under:

`Settings > iNaturalist Observations`

These defaults are used by the block and shortcode unless page-specific block settings override them.

Use `Clear iNaturalist cache` on the settings page when source settings change or when cached observations need to refresh immediately.

## Accessibility

The rendered block is built with semantic sections, labeled filter and pagination navigation, visible keyboard focus styles, informative image alt text, and screen-reader text for external iNaturalist links that open in a new tab.

Latest local check:

```sh
/Users/lwangdu/.npm-global/bin/pa11y http://localhost:8896/inaturalist-observations/
```

Result: no issues found.

## Development Checks

Install development dependencies:

```sh
composer install
```

Run PHP syntax checks:

```sh
composer lint:php
```

Run JavaScript syntax check:

```sh
composer lint:js
```

Run automated WordPress Coding Standards checks with the `WordPress-Core` ruleset:

```sh
composer phpcs
```
