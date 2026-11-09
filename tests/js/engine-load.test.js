import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createEngineLoader } from '../../assets/js/modules/engine-load.js';

function fakeDoc() {
  const appended = [];
  return {
    appended,
    createElement(tag) {
      return { tag, src: '', onload: null, onerror: null };
    },
    head: {
      appendChild(el) { appended.push(el); },
    },
  };
}

test('load injects the engine script exactly once across repeated calls', async () => {
  const doc = fakeDoc();
  const loader = createEngineLoader(doc, '/wp-content/plugins/swarm-rebuild/assets/js/engine/swarm.js');
  const first = loader.load();
  doc.appended[0].onload();
  assert.strictEqual(await first, true);
  const second = loader.load();
  assert.strictEqual(await second, true);
  assert.strictEqual(doc.appended.length, 1);
  assert.strictEqual(doc.appended[0].src, '/wp-content/plugins/swarm-rebuild/assets/js/engine/swarm.js');
});

test('isLoaded reflects load state', async () => {
  const doc = fakeDoc();
  const loader = createEngineLoader(doc, 'x.js');
  const p = loader.load();
  assert.strictEqual(loader.isLoaded(), false);
  doc.appended[0].onload();
  await p;
  assert.strictEqual(loader.isLoaded(), true);
});

test('a failed load resolves false and never rejects (stale-cache 404 must not throw)', async () => {
  const doc = fakeDoc();
  const loader = createEngineLoader(doc, 'missing.js');
  const p = loader.load();
  doc.appended[0].onerror();
  assert.strictEqual(await p, false);
  assert.strictEqual(loader.isLoaded(), false);
});

test('after a failed load, later calls stay false without re-injecting', async () => {
  const doc = fakeDoc();
  const loader = createEngineLoader(doc, 'missing.js');
  const p1 = loader.load();
  doc.appended[0].onerror();
  assert.strictEqual(await p1, false);
  assert.strictEqual(await loader.load(), false);
  assert.strictEqual(doc.appended.length, 1);
});
