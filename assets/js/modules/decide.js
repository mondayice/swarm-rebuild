/**
 * Auto-play judgment chain (pinned in the plan, order is semantic):
 *   1. prefers-reduced-motion  → never animate
 *   2. visitor opted out       → their browser, their choice
 *   3. autoplay not armed      → nothing to decide
 *   4. intro mode + seen once site-wide → don't repeat
 *   5. play
 * Shortcode autoplay ignores rule 4 (page-level demo intent); scope was already
 * enforced server-side by the enqueue gate.
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
  if (config.autoplayMode === 'intro' && state.seenSiteWide) {
    return { play: false, reason: 'seen' };
  }
  return { play: true, reason: 'play' };
}
