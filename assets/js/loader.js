/**
 * Swarm Rebuild loader — the always-light front layer.
 *
 * Responsibilities (everything heavy lives in the lazily-injected engine):
 *  - read the #swarm-rebuild-config JSON tag printed by PHP (missing/broken → silent no-op)
 *  - render the visitor control card (bottom-left, collapsible to a left-edge handle)
 *  - Alt+Shift+S hotkey, ?swarm=1 URL param, [swarm_rebuild autoplay] marker merge
 *  - judgment chain: reduced-motion > visitor opt-out > (intro) seen-once-site-wide
 *  - pre-erase over-limit precheck BEFORE the engine is ever loaded
 *  - lazy-inject the engine once, then drive it through window.SwarmRebuild
 *  - autoplay (intro or shortcode) auto-exits to the live page after introDelay;
 *    manual triggers (hotkey, param, card replay) never auto-exit
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

  let currentTrigger = null; // 'auto' | 'manual' — set when a run starts
  let introTimer = null;
  let soundOn = prefs.hasSound() ? prefs.sound() : !!cfg.soundDefault;

  const reducedMotion = () =>
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const measure = () => ({
    scrollHeight: document.documentElement.scrollHeight,
    elementCount: document.getElementsByTagName('*').length,
  });

  function resolveSound() {
    return soundOn;
  }

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
      soundDefault: resolveSound(),
    };

    engineLoader.load().then((ok) => {
      if (!ok || !window.SwarmRebuild) return;
      const api = window.SwarmRebuild;

      api.onComplete(() => {
        if (currentTrigger === 'auto') {
          introTimer = setTimeout(() => {
            if (window.SwarmRebuild && window.SwarmRebuild.app) window.SwarmRebuild.goLive();
          }, Math.max(0.5, cfg.introDelay || 2.5) * 1000);
        }
      });

      if (api.app) api.stop(false); // finished state → replay re-wipes
      currentTrigger = trigger;
      api.start();
      if (trigger === 'auto') prefs.markSeen();
    });
    return true;
  }

  function manualTrigger() {
    clearTimeout(introTimer);
    run('manual');
  }

  /* ---- autoplay decision (intro mode + shortcode marker merge) ---- */

  let autoplay = !!cfg.autoplay;
  let autoplayMode = cfg.autoplay ? cfg.autoplayMode : null;
  const marker = document.getElementById('swarm-rebuild-shortcode');
  if (marker) {
    try {
      if (JSON.parse(marker.textContent).autoplay) {
        autoplay = true;
        autoplayMode = 'shortcode';
      }
    } catch {
      /* malformed marker: ignore */
    }
  }

  const decision = decideAutoPlay(
    { autoplay, autoplayMode: autoplay ? autoplayMode : null },
    { reducedMotion: reducedMotion(), optedOut: prefs.optedOut(), seenSiteWide: prefs.seen() }
  );
  if (decision.play) run('auto');

  /* ---- hotkey ---- */

  if (cfg.hotkeyEnabled) {
    document.addEventListener('keydown', (e) => {
      if (e.altKey && e.shiftKey && e.code === 'KeyS') {
        e.preventDefault();
        manualTrigger();
      }
    });
  }

  /* ---- ?swarm=1 (consume the param so a refresh doesn't retrigger) ---- */

  const params = new URLSearchParams(window.location.search);
  if (params.get('swarm') === '1') {
    params.delete('swarm');
    const qs = params.toString();
    window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : '') + window.location.hash);
    manualTrigger();
  }

  /* ---- visitor card ---- */

  if (!cfg.cardEnabled) return;

  const strings = cfg.strings || {};
  const host = document.createElement('div');
  host.id = 'swarm-rebuild-card-host';
  if (prefs.collapsed()) host.classList.add('sr-collapsed');
  host.innerHTML = [
    '<div class="sr-card" role="group" aria-label="Swarm Rebuild">',
    `<button type="button" class="sr-btn" data-sr="replay">${strings.cardReplay || 'Replay'}</button>`,
    `<button type="button" class="sr-btn" data-sr="close">${strings.cardClose || 'Close animation'}</button>`,
    `<button type="button" class="sr-btn" data-sr="sound">${strings.cardSound || 'Sound'}</button>`,
    `<button type="button" class="sr-btn sr-btn-quiet" data-sr="hide">${strings.cardHide || 'Hide'}</button>`,
    '</div>',
    `<button type="button" class="sr-handle" data-sr="show" title="${strings.cardShow || 'Show'}">${strings.cardShow || 'Show'}</button>`,
  ].join('');
  document.body.appendChild(host);

  const soundBtn = host.querySelector('[data-sr="sound"]');
  const syncSound = () => soundBtn.classList.toggle('sr-on', soundOn);
  syncSound();

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
        api.goLive();
      } else {
        // Idle: this browser stops auto-playing the intro.
        prefs.setOptedOut(true);
        btn.classList.add('sr-done');
      }
    } else if (action === 'sound') {
      soundOn = !soundOn;
      prefs.setSound(soundOn);
      if (window.SwarmRebuildEngineConfig) window.SwarmRebuildEngineConfig.soundDefault = soundOn;
      syncSound();
    } else if (action === 'hide') {
      host.classList.add('sr-collapsed');
      prefs.setCollapsed(true);
    } else if (action === 'show') {
      host.classList.remove('sr-collapsed');
      prefs.setCollapsed(false);
    }
  });
})();
