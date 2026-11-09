=== Swarm Rebuild ===
Contributors: swarmrebuild
Tags: animation, intro, cursor, effects
Requires at least: 6.5
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wipe the page to a blank canvas and let a swarm of multiplayer cursors rebuild it, live: crosshair-drawn boxes, typed text, pen-tool lines, ruler guides and alt-drag duplicates — rebuilt links become clickable while the swarm is still building.

== Description ==

Swarm Rebuild turns opening a page into a performance: the page fades to a canvas, a swarm of cursors fans out, and they rebuild the page piece by piece. The rebuild replays on every visit to pages in scope.

* **Clickable while building:** as soon as a link's zone has been rebuilt, it lights up and is clickable — visitors can navigate without waiting for the whole page.
* **Background stays visible:** fixed full-viewport background layers (canvas/video wallpaper) remain live under the animation instead of being wiped to a flat color.
* **One control card:** a small card in the bottom-left corner offers replay, close, sound and tour; it collapses to the left edge. The animation's own bar keeps only a status strip (agents, progress).
* **Shortcode:** `[swarm_rebuild]` enables the page anywhere; add `autoplay="true"` to play on load.
* **URL param:** append `?swarm=1` to any page in scope — great for screen recordings.
* **Visitor choice:** Esc (or the card's close button while idle) stops auto-playing in that browser; `prefers-reduced-motion` is always respected.

The real DOM is never replaced — the rebuild is an overlay, so your content and SEO stay untouched. Pages over the size limit (very long pages, heavy page-builder layouts) are skipped gracefully instead of animating.

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

Their choice is stored in their browser (localStorage). Clearing site data in the browser restores the animation.

= Why does nothing happen on my phone? =

Mobile is disabled by default (Settings → Swarm Rebuild) because touch devices have no cursor metaphor. You can enable it.

= The rebuild finished but the page stayed on the drawing? =

"Return to the live page when finished" is a setting. When it is off, the rebuilt page stays (links keep working) until the visitor clicks a link, presses Esc, or uses the card.

= I put the shortcode in an Elementor Shortcode widget and nothing happens? =

Shortcodes inside Elementor widgets are detected (builder data is scanned). If you use another page builder that stores content elsewhere, place the shortcode in a regular content block.

== Changelog ==

= 0.2.0 =
* Simplified to the product goal: the rebuild replays on every visit (no intro-mode toggle, no once-per-visitor rule).
* Rebuilt links are clickable during the animation — each link's proxy lights up when the tasks rebuilding it finish.
* Transparent backdrop: fixed full-viewport background media stays visible while the swarm rebuilds the content.
* Controls unified into the visitor card (replay, close, sound, tour, collapse); the engine bar keeps only the status strip. Sound and tour toggle live.
* Settings reduced to: where to enable, auto-return after finishing, sound default, mobile.

= 0.1.0 =
* Initial build: engine port, loader, visitor card, settings page, shortcode, intro mode.
