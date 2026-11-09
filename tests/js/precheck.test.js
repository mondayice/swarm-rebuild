import { test } from 'node:test';
import assert from 'node:assert/strict';
import { precheckOk } from '../../assets/js/modules/precheck.js';

test('at the limits the page is allowed', () => {
  assert.strictEqual(precheckOk({ scrollHeight: 40000, elementCount: 6000 }), true);
});

test('over either limit the page is rejected', () => {
  assert.strictEqual(precheckOk({ scrollHeight: 40001, elementCount: 10 }), false);
  assert.strictEqual(precheckOk({ scrollHeight: 100, elementCount: 6001 }), false);
});

test('small pages pass', () => {
  assert.strictEqual(precheckOk({ scrollHeight: 3000, elementCount: 800 }), true);
});

test('defaults apply when thresholds omitted', () => {
  assert.strictEqual(precheckOk({ scrollHeight: 40000, elementCount: 1 }), true);
  assert.strictEqual(precheckOk({ scrollHeight: 1, elementCount: 6000 }), true);
  assert.strictEqual(precheckOk({ scrollHeight: 100000, elementCount: 1 }), false);
  assert.strictEqual(precheckOk({ scrollHeight: 1, elementCount: 99999 }), false);
});
