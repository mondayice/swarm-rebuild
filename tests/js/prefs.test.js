import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createPrefs } from '../../assets/js/modules/prefs.js';

function memoryStorage() {
  const map = new Map();
  const touched = [];
  return {
    touched,
    getItem(k) { touched.push(['get', k]); return map.has(k) ? map.get(k) : null; },
    setItem(k, v) { touched.push(['set', k]); map.set(k, String(v)); },
    removeItem(k) { touched.push(['remove', k]); map.delete(k); },
  };
}

test('all flags default to false', () => {
  const p = createPrefs(memoryStorage());
  assert.strictEqual(p.seen(), false);
  assert.strictEqual(p.optedOut(), false);
  assert.strictEqual(p.collapsed(), false);
  assert.strictEqual(p.sound(), false);
});

test('setters persist and getters read back', () => {
  const p = createPrefs(memoryStorage());
  p.markSeen();
  p.setOptedOut(true);
  p.setCollapsed(true);
  p.setSound(true);
  assert.strictEqual(p.seen(), true);
  assert.strictEqual(p.optedOut(), true);
  assert.strictEqual(p.collapsed(), true);
  assert.strictEqual(p.sound(), true);
});

test('clearing a flag removes the key', () => {
  const s = memoryStorage();
  const p = createPrefs(s);
  p.setOptedOut(true);
  p.setOptedOut(false);
  assert.strictEqual(p.optedOut(), false);
  assert.ok(s.touched.some(([op, k]) => op === 'remove' && k === 'srOptOut'));
});

test('localStorage keys are the pinned site-wide names', () => {
  const s = memoryStorage();
  const p = createPrefs(s);
  p.markSeen();
  p.setOptedOut(true);
  p.setCollapsed(true);
  p.setSound(true);
  const setKeys = s.touched.filter(([op]) => op === 'set').map(([, k]) => k);
  assert.deepStrictEqual(setKeys.sort(), ['srCardCollapsed', 'srIntroSeen', 'srOptOut', 'srSound'].sort());
});

test('a throwing storage (private mode) never breaks the caller', () => {
  const boom = {
    getItem() { throw new Error('quota'); },
    setItem() { throw new Error('quota'); },
    removeItem() { throw new Error('quota'); },
  };
  const p = createPrefs(boom);
  assert.strictEqual(p.seen(), false);
  assert.doesNotThrow(() => p.markSeen());
  assert.doesNotThrow(() => p.setOptedOut(true));
  assert.strictEqual(p.optedOut(), false);
});
