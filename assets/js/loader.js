/**
 * Swarm Rebuild loader — the always-light front layer.
 *
 * Responsibilities (everything heavy lives in the lazily-injected engine):
 *  - read the #swarm-rebuild-config JSON tag printed by PHP (missing/broken → silent no-op)
 *  - render the visitor control card (bottom-left, collapsible to a left-edge
 *    handle) — the ONLY control surface during a run (0.2.0: engine HUD buttons
 *    are gone; the HUD keeps just the status strip)
 *  - ?swarm=1 URL param, [swarm_rebuild autoplay] marker merge
 *  - judgment chain: reduced-motion > visitor opt-out > play (every visit replays)
 *  - pre-erase over-limit precheck BEFORE the engine is ever loaded
 *  - lazy-inject the engine once, then drive it through window.SwarmRebuild
 *  - when the admin enabled auto-return, EVERY finished run (auto or manual)
 *    exits the canvas within ~0.5s of completion: a 0.2s beat, then the
 *    engine's fast fade-out — the graceful "go live" fade stays for the card
 *  - Esc (or the card's close button while idle) stops auto-playing in this browser
 */

import { decideAutoPlay } from './modules/decide.js';
import { precheckOk } from './modules/precheck.js';
import { createPrefs } from './modules/prefs.js';
import { createEngineLoader } from './modules/engine-load.js';

(() => {
  const configTag = document.getElementById('swarm-rebuild-config');
  if (!configTag) return;
  let cfg;
  try {
    cfg = JSON.parse(configTag.textContent);
  } catch {
    return;
  }

  const prefs = createPrefs(window.localStorage);
  const engineLoader = createEngineLoader(document, cfg.engineUrl);

  // Finished-run exit budget: completion + 0.1s beat + engine's 0.26s fast
  // fade ≈ canvas gone ~0.36s after the last task lands (spec: ≤0.5s).
  const AUTO_EXIT_DELAY = 100;

  let currentTrigger = null; // 'auto' | 'manual' — set when a run starts
  let introTimer = null;
  let cardHost = null; // set once the visitor card exists
  let completeHooked = false; // engine completion callback registers once, not per run
  let soundOn = prefs.hasSound() ? prefs.sound() : !!cfg.soundDefault;
  let tourOn = cfg.tourDefault !== false; // admin default until a run mirrors the live state

  const reducedMotion = () =>
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const measure = () => ({
    scrollHeight: document.documentElement.scrollHeight,
    elementCount: document.getElementsByTagName('*').length,
  });

  /**
   * Start (or restart) a run. Returns false without side effects when
   * reduced-motion applies or the page is over the precheck limits — the
   * engine is never even loaded in that case.
   */
  function run(trigger) {
    if (reducedMotion()) return false;
    if (!precheckOk(measure())) return false;

    window.SwarmRebuildEngineConfig = {
      strings: cfg.strings || {},
      soundDefault: soundOn,
      tourDefault: cfg.tourDefault !== false,
      backdrop: cfg.backdrop === 'off' ? 'off' : 'auto',
      wallpaperSelectors: Array.isArray(cfg.wallpaperSelectors) ? cfg.wallpaperSelectors : [],
    };

    engineLoader.load().then((ok) => {
      if (!ok || !window.SwarmRebuild) return;
      const api = window.SwarmRebuild;

      if (!completeHooked) {
        completeHooked = true;
        api.onComplete(() => {
          if (cfg.autoReturn !== false) {
            clearTimeout(introTimer);
            introTimer = setTimeout(() => {
              currentTrigger = null; // our own exit must not read as a visitor exit
              // Fast canvas exit (≤0.5s after completion): the graceful
              // goLive fade is reserved for the card's close button.
              if (window.SwarmRebuild && window.SwarmRebuild.app) window.SwarmRebuild.stop(false);
            }, AUTO_EXIT_DELAY);
          }
        });
      }

      if (api.app) api.stop(false); // finished state → replay re-wipes
      currentTrigger = trigger;
      api.start();
      // The engine overlay (appended to <html>, max z-index, pointer-events on)
      // would cover the card: re-append the card host to <html> AFTER start so
      // tree order puts the card on top for this run (review C2).
      if (cardHost) document.documentElement.appendChild(cardHost);
      // Each run defaults tour ON; mirror the fresh run's state onto the card.
      tourOn = api.app ? !!api.app.tour : true;
      syncTour();
    });
    return true;
  }

  function manualTrigger() {
    clearTimeout(introTimer);
    run('manual');
  }

  /* ---- autoplay decision (every-visit; shortcode marker merge) ---- */

  let autoplay = !!cfg.autoplay;
  const marker = document.getElementById('swarm-rebuild-shortcode');
  if (marker) {
    try {
      if (JSON.parse(marker.textContent).autoplay) autoplay = true;
    } catch {
      /* malformed marker: ignore */
    }
  }

  const decision = decideAutoPlay(
    { autoplay },
    { reducedMotion: reducedMotion(), optedOut: prefs.optedOut() }
  );
  if (decision.play) run('auto');

  /* ---- ?swarm=1 (consume the param so a refresh doesn't retrigger) ---- */

  const params = new URLSearchParams(window.location.search);
  if (params.get('swarm') === '1') {
    params.delete('swarm');
    const qs = params.toString();
    window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : '') + window.location.hash);
    manualTrigger();
  }

  /* ---- Esc during an auto run = this browser stops auto-playing ---- */
  // Window-capture, registered BEFORE the engine loads: the engine's own Esc
  // binding (also window-capture) calls stopPropagation(), which never blocks
  // same-target listeners — but it WOULD block a document-level one.

  window.addEventListener(
    'keydown',
    (e) => {
      if (e.key !== 'Escape') return;
      if (currentTrigger === 'auto' && window.SwarmRebuild && window.SwarmRebuild.app) {
        prefs.setOptedOut(true);
      }
    },
    true
  );

  /* ---- visitor card (the sole control surface during a run) ---- */

  const strings = cfg.strings || {};
  const host = document.createElement('div');
  cardHost = host;
  host.id = 'swarm-rebuild-card-host';
  if (prefs.collapsed()) host.classList.add('sr-collapsed');
  host.innerHTML = [
    '<div class="sr-card" role="group" aria-label="Swarm Rebuild">',
    `<button type="button" class="sr-btn" data-sr="replay">${strings.cardReplay || 'Replay'}</button>`,
    `<button type="button" class="sr-btn" data-sr="close">${strings.cardClose || 'Close animation'}</button>`,
    `<button type="button" class="sr-btn" data-sr="sound">${strings.cardSound || 'Sound'}</button>`,
    `<button type="button" class="sr-btn" data-sr="tour">${strings.cardTour || 'Tour'}</button>`,
    `<button type="button" class="sr-btn sr-btn-quiet" data-sr="hide">${strings.cardHide || 'Hide'}</button>`,
    '</div>',
    `<button type="button" class="sr-handle" data-sr="show" title="${strings.cardShow || 'Show'}">${strings.cardShow || 'Show'}</button>`,
  ].join('');
  document.body.appendChild(host);

  const soundBtn = host.querySelector('[data-sr="sound"]');
  const tourBtn = host.querySelector('[data-sr="tour"]');
  // Sound applies immediately while a run is live (setSound) and seeds the next
  // run via the engine config; M toggles it during the animation as well.
  if (strings.cardSoundHint) soundBtn.title = strings.cardSoundHint;
  if (strings.cardTourHint) tourBtn.title = strings.cardTourHint;
  const syncSound = () => soundBtn.classList.toggle('sr-on', soundOn);
  const syncTour = () => {
    tourBtn.classList.toggle('sr-on', tourOn);
    tourBtn.textContent = tourOn ? strings.cardTouring || 'Touring' : strings.cardTour || 'Tour';
  };
  syncSound();
  syncTour();

  host.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-sr]');
    if (!btn) return;
    const action = btn.dataset.sr;

    if (action === 'replay') {
      manualTrigger();
    } else if (action === 'close') {
      const api = window.SwarmRebuild;
      if (api && api.app) {
        // Playing: skip to the live page.
        clearTimeout(introTimer);
        currentTrigger = null; // a deliberate skip is not a lasting opt-out…
        api.goLive();
      } else {
        // …but when idle it means: this browser stops auto-playing.
        prefs.setOptedOut(true);
        btn.classList.add('sr-done');
      }
    } else if (action === 'sound') {
      soundOn = !soundOn;
      prefs.setSound(soundOn);
      if (window.SwarmRebuildEngineConfig) window.SwarmRebuildEngineConfig.soundDefault = soundOn;
      if (window.SwarmRebuild && window.SwarmRebuild.app) window.SwarmRebuild.setSound(soundOn);
      syncSound();
    } else if (action === 'tour') {
      tourOn = !tourOn;
      if (window.SwarmRebuild && window.SwarmRebuild.app) window.SwarmRebuild.setTour(tourOn);
      syncTour();
    } else if (action === 'hide') {
      host.classList.add('sr-collapsed');
      prefs.setCollapsed(true);
    } else if (action === 'show') {
      host.classList.remove('sr-collapsed');
      prefs.setCollapsed(false);
    }
  });
})();
