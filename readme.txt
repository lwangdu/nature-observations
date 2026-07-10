=== Field Observation Showcase ===
Contributors: lobsangw
Tags: inaturalist, observations, biodiversity, maps, block
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.2.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display cached nature observations in WordPress with block editor support, observation cards, filters, stats, pagination, and a map view.

== Description ==

Field Observation Showcase displays iNaturalist observations on WordPress sites. It provides dynamic blocks for an observation grid and a compact observation map.

This plugin is an independent WordPress plugin that displays publicly available observation data from iNaturalist. It is not affiliated with, endorsed by, or sponsored by iNaturalist.

Features include:

* Dynamic iNaturalist Observations block.
* Dynamic iNaturalist Observations Map block.
* Source options for iNaturalist project slug, project ID, place ID, or user/account login.
* Observation cards with photo, common name, scientific name, observation date, observer, and quality grade.
* Filters for all observations, birds, mammals, plants, insects, and fungi.
* Stats cards for observations shown on the current page and all-time source totals.
* Pagination for larger projects.
* Cached iNaturalist API requests using WordPress transients.
* Map view with reserve boundary and recent observation thumbnails.
* Optional setting for opening iNaturalist links in a new tab.
* Admin cache clearing.
* Admin-only CSV export for all observations from the configured source.

= Third-party services =

This plugin connects to third-party services to display observation and map data. These requests happen when a visitor views a page containing one of the plugin blocks, when an editor previews the block in the editor, when an administrator uses the CSV export, or when WordPress runs the plugin cache warmer.

* iNaturalist API and observation media: The plugin sends the configured iNaturalist source values, such as project slug, project ID, place ID, user ID/login, page number, per-page count, and selected taxon filter, to the public iNaturalist API. It uses these requests to retrieve observations, source statistics, project data, place data, place boundary geometry, and observation photo URLs. Front-end pages may load observation images from the image URLs returned by iNaturalist, including iNaturalist/Open Data storage URLs. No WordPress user account data is intentionally sent by the plugin. iNaturalist API documentation: https://api.inaturalist.org/v1/docs/ Terms: https://www.inaturalist.org/terms Privacy policy: https://www.inaturalist.org/privacy
* Esri ArcGIS World Topographic Map tiles: The map block loads terrain/topographic map tiles from Esri ArcGIS Online when a visitor or editor views the map block. Browser requests to Esri include normal web request data such as IP address, user agent, referring page, and requested map tile coordinates. Esri terms: https://www.esri.com/en-us/legal/terms/full-master-agreement Privacy statement: https://www.esri.com/en-us/privacy/overview

= Bundled third-party libraries =

This plugin bundles Leaflet 1.9.4 for the interactive map. Leaflet is licensed under the BSD 2-Clause License. The license is included at assets/vendor/leaflet/LICENSE. Leaflet project: https://leafletjs.com/

== Installation ==

1. Upload the plugin folder to the /wp-content/plugins/ directory, or install it through the WordPress Plugins screen.
2. Activate the plugin through the Plugins screen in WordPress.
3. Go to Settings > Field Observation Showcase to configure default source settings.
4. Add the iNaturalist Observations block or iNaturalist Observations Map block to a page.

The plugin creates draft starter iNaturalist Observations and Map of Observations pages on activation if those pages do not already exist. Review and publish those pages when ready.

== Frequently Asked Questions ==

= Does this plugin require an iNaturalist API key? =

No. It uses public iNaturalist API endpoints.

= Does this plugin store observation data permanently? =

The plugin caches API responses in WordPress transients to reduce page-load time and API pressure. The cache can be cleared from the plugin settings page.

== Screenshots ==

1. Observation grid with filters, stats, cards, and pagination.
2. Observation map with reserve boundary, pins, and recent observation thumbnails.

== Changelog ==

= 0.2.8 =
* Renamed the plugin to Field Observation Showcase and updated the plugin slug to avoid trademark concerns.
* Kept compatibility aliases for previously saved block names.

= 0.2.7 =
* Renamed the plugin for WordPress.org review clarity.
* Updated plugin slug, text domain, and third-party service disclosure for iNaturalist and Esri ArcGIS Online.

= 0.2.6 =
* Stream admin CSV exports for large iNaturalist datasets and avoid HTML error output in CSV files.
* Removed legacy shortcode support so the plugin is block-only.
* Cleaned unused code and plugin-check warnings before submission.

= 0.2.5 =
* Added an admin-only CSV export button that exports all observations from the configured source.

= 0.2.4 =
* Cached the resolved cache-warmer source list and narrowed source discovery to posts containing plugin blocks.

= 0.2.3 =
* Added stale cache fallback and short-lived API error caching for better cold-cache resilience.
* Warm caches for sources found in saved blocks instead of only the settings default.
* Track cache keys so cache clearing works with persistent object caches.
* Removed hardcoded reserve source defaults and added block metadata, JavaScript translations, prefixed Leaflet handles, and uninstall cleanup.

= 0.2.2 =
* Removed the hardcoded demo-project boundary override so all project boundaries use the generic iNaturalist lookup path.
* Added background cache warming and transient locks to reduce cold-cache API pressure.
* Increased map marker loading for closer parity with iNaturalist map views.

= 0.2.1 =
* Added observation map block.
* Added reserve boundary display and terrain map tiles.
* Added recent observations carousel with WordPress Interactivity API enhancements.
* Added pagination prefetch/loading state with the WordPress Interactivity API.
* Bundled Leaflet locally and documented third-party services.
* Starter pages are created as drafts for review before publishing.

= 0.1.1 =
* Added cached iNaturalist observation block, filters, stats, pagination, and admin settings.
