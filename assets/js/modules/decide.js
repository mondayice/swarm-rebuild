/**
 * Auto-play judgment chain (0.2.0, order is semantic):
 *   1. prefers-reduced-motion  → never animate
 *   2. visitor opted out       → their browser, their choice
 *   3. autoplay not armed      → nothing to decide
 *   4. play
 * Every visit replays (the rebuild is the page-opening experience); scope was
 * already enforced server-side by the enqueue gate.
 */

export function decideAutoPlay(config, state) {
  if (state.reducedMotion) {
    return { play: false, reason: 'reduced-motion' };
  }
  if (state.optedOut) {
    return { play: false, reason: 'opted-out' };
  }
  if (!config.autoplay) {
    return { play: false, reason: 'off' };
  }
  return { play: true, reason: 'play' };
}
