import { test } from 'node:test';
import assert from 'node:assert/strict';
import { decideAutoPlay } from '../../assets/js/modules/decide.js';

// 0.2.0: every visit replays — the seen-once rule is gone.

test('reduced-motion short-circuits everything, even with autoplay on', () => {
  const out = decideAutoPlay({ autoplay: true }, { reducedMotion: true, optedOut: false });
  assert.deepStrictEqual(out, { play: false, reason: 'reduced-motion' });
});

test('reduced-motion outranks visitor opt-out', () => {
  const out = decideAutoPlay({ autoplay: true }, { reducedMotion: true, optedOut: true });
  assert.strictEqual(out.reason, 'reduced-motion');
});

test('visitor opt-out blocks autoplay but not manual triggers', () => {
  const out = decideAutoPlay({ autoplay: true }, { reducedMotion: false, optedOut: true });
  assert.deepStrictEqual(out, { play: false, reason: 'opted-out' });
});

test('autoplay off reports off', () => {
  const out = decideAutoPlay({ autoplay: false }, { reducedMotion: false, optedOut: false });
  assert.deepStrictEqual(out, { play: false, reason: 'off' });
});

test('a returning visitor replays every visit (no seen flag anymore)', () => {
  const out = decideAutoPlay({ autoplay: true }, { reducedMotion: false, optedOut: false });
  assert.deepStrictEqual(out, { play: true, reason: 'play' });
});
