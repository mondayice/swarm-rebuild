=== Swarm Rebuild ===
Contributors: swarmrebuild
Tags: animation, easter-egg, intro, cursor, effects
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wipe the page to a blank canvas and let a swarm of multiplayer cursors rebuild it, live: crosshair-drawn boxes, typed text, pen-tool lines, ruler guides and alt-drag duplicates — with synthesized sound.

== Description ==

Swarm Rebuild turns any page on your site into a performance: the page fades to a blank canvas, a swarm of cursors fans out, and they rebuild the page piece by piece until it looks exactly like it did before.

* **Easter egg (default):** Alt+Shift+S on any page in scope triggers the rebuild.
* **Shortcode:** `[swarm_rebuild]` enables the page anywhere; add `autoplay="true"` to play on load.
* **URL param:** append `?swarm=1` to any page in scope — great for screen recordings.
* **Intro mode (off by default):** auto-play the rebuild once per visitor; visitors can skip or turn it off in their own browser.
* **Visitor control card:** a small card in the bottom-left corner offers replay, stop and sound; it collapses to the left edge.

The real DOM is never replaced — the rebuild is an overlay, so your content and SEO stay untouched. `prefers-reduced-motion` is always respected. Pages over the size limit (very long pages, heavy page-builder layouts) are skipped gracefully instead of animating.

= Credits =

The motion design and animation engine are adapted from **Swarm** by Joel Califa (https://joelcalifa.com, https://califa.github.io/swarm-site/), published at https://github.com/califa/swarm-site. Adaptation status: permission request pending — this plugin will not be submitted to the WordPress.org directory until written permission is recorded here.

== Installation ==

1. Upload the `swarm-rebuild` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload.
2. Activate the plugin.
3. Open Settings → Swarm Rebuild and choose where the swarm may run.

== Frequently Asked Questions ==

= Does the animation replace my content? =

No. It snapshots the page and rebuilds a visual copy on an overlay. Your real HTML stays in the source at all times.

= A visitor turned the animation off. How do they get it back? =

Their choice is stored in their browser (localStorage). Clearing site data in the browser restores the intro.

= Why does nothing happen on my phone? =

Mobile is disabled by default (Settings → Swarm Rebuild) because touch devices have no cursor metaphor. You can enable it.

= I toggled sound on the card but the running animation kept playing sounds. =

The card toggle governs runs from the next page load; during a run, use the sound button (M) in the animation's own bar.

= I put the shortcode in an Elementor Shortcode widget and nothing happens? =

Shortcodes inside Elementor widgets are detected (builder data is scanned). If you use another page builder that stores content elsewhere, place the shortcode in a regular content block.

== Changelog ==

= 0.1.0 =
* Initial build: engine port, loader, visitor card, settings page, shortcode, intro mode.
