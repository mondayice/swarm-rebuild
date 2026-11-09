import { test } from 'node:test';
import assert from 'node:assert/strict';
import { decideAutoPlay } from '../../assets/js/modules/decide.js';

test('reduced-motion short-circuits everything, even with autoplay on', () => {
  const out = decideAutoPlay(
    { autoplay: true, autoplayMode: 'intro' },
    { reducedMotion: true, optedOut: false, seenSiteWide: false }
  );
  assert.deepStrictEqual(out, { play: false, reason: 'reduced-motion' });
});

test('reduced-motion outranks visitor opt-out', () => {
  const out = decideAutoPlay(
    { autoplay: true, autoplayMode: 'intro' },
    { reducedMotion: true, optedOut: true, seenSiteWide: false }
  );
  assert.strictEqual(out.reason, 'reduced-motion');
});

test('visitor opt-out blocks autoplay but not manual triggers', () => {
  const out = decideAutoPlay(
    { autoplay: true, autoplayMode: 'intro' },
    { reducedMotion: false, optedOut: true, seenSiteWide: false }
  );
  assert.deepStrictEqual(out, { play: false, reason: 'opted-out' });
});

test('autoplay off reports off', () => {
  const out = decideAutoPlay(
    { autoplay: false, autoplayMode: null },
    { reducedMotion: false, optedOut: false, seenSiteWide: false }
  );
  assert.deepStrictEqual(out, { play: false, reason: 'off' });
});

test('intro mode on a site-wide-seen browser reports seen', () => {
  const out = decideAutoPlay(
    { autoplay: true, autoplayMode: 'intro' },
    { reducedMotion: false, optedOut: false, seenSiteWide: true }
  );
  assert.deepStrictEqual(out, { play: false, reason: 'seen' });
});

test('intro mode on a fresh browser plays', () => {
  const out = decideAutoPlay(
    { autoplay: true, autoplayMode: 'intro' },
    { reducedMotion: false, optedOut: false, seenSiteWide: false }
  );
  assert.deepStrictEqual(out, { play: true, reason: 'play' });
});

test('shortcode mode ignores the seen flag (page-level demo intent)', () => {
  const out = decideAutoPlay(
    { autoplay: true, autoplayMode: 'shortcode' },
    { reducedMotion: false, optedOut: false, seenSiteWide: true }
  );
  assert.deepStrictEqual(out, { play: true, reason: 'play' });
});
