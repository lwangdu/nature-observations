# UC Nature iNaturalist Observations

A small WordPress plugin for displaying live iNaturalist observations on UC Nature sites. The plugin pulls observations from the iNaturalist API, caches responses with WordPress transients, and renders a Gutenberg block with cards, stats, filters, and pagination.

## Features

- Dynamic `iNaturalist Observations` block.
- iNaturalist source options for project slug, project ID fallback, place ID, or user/account login.
- Observation cards with photo, common name, scientific name, observation date, observer, and quality grade.
- Filters for all observations, birds, mammals, plants, insects, and fungi.
- Stats cards for observations shown on the current page and all-time source totals.
- Pagination for larger projects, with 100 observations per page by default.
- Cached iNaturalist API requests to reduce page-load and API pressure.
- Legacy shortcode support via `[ucnature_inat_observations]`.

## Usage

1. Activate the plugin in WordPress.
2. Open or create an `iNaturalist Observations` page.
3. Add the `iNaturalist Observations` block.
4. Configure the source in the block sidebar:
   - `Project slug` for an iNaturalist project slug.
   - `Project ID fallback` when a numeric project ID is needed.
   - `Place ID` for reserve boundary-based feeds.
   - `User ID or login` for account-based feeds.
   - `Observations per page` for pagination size.

The plugin creates a starter `iNaturalist Observations` page on activation if one does not already exist.

## Settings

Global defaults are available under:

`Settings > iNaturalist Observations`

These defaults are used by the block and shortcode unless page-specific block settings override them.

## Accessibility

The rendered block is built with semantic sections, labeled filter and pagination navigation, visible keyboard focus styles, informative image alt text, and screen-reader text for external iNaturalist links that open in a new tab.

Latest local check:

```sh
/Users/lwangdu/.npm-global/bin/pa11y http://localhost:8896/inaturalist-observations/
```

Result: no issues found.

## Development Checks

Run PHP syntax checks:

```sh
find . -name '*.php' -print -exec php -l {} \;
```

Run JavaScript syntax check:

```sh
node --check assets/js/block.js
```

For automated WordPress Coding Standards checks, install WordPressCS and run:

```sh
phpcs --standard=WordPress --extensions=php .
```

In the current local environment, PHPCS is installed but the `WordPress` standard is not installed.
