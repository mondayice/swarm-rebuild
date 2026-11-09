/**
 * Visitor preferences, all site-wide (no per-URL keys). Storage is injected so
 * tests run against a memory map and private-mode Storage that throws on every
 * access degrades to defaults without ever breaking the caller.
 */

const KEY_OPT_OUT = 'srOptOut';
const KEY_COLLAPSED = 'srCardCollapsed';
const KEY_SOUND = 'srSound';

function safeGet(storage, key) {
  try {
    return storage.getItem(key) === '1';
  } catch {
    return false;
  }
}

function safeHas(storage, key) {
  try {
    return storage.getItem(key) !== null;
  } catch {
    return false;
  }
}

function safeSet(storage, key, value) {
  try {
    // Store explicit '1'/'0' so "off" stays distinguishable from "never set"
    // (hasSound relies on that distinction to fall back to the admin default).
    storage.setItem(key, value ? '1' : '0');
  } catch {
    /* private mode: preference simply doesn't persist */
  }
}

export function createPrefs(storage) {
  return {
    optedOut() { return safeGet(storage, KEY_OPT_OUT); },
    setOptedOut(value) { safeSet(storage, KEY_OPT_OUT, value); },
    collapsed() { return safeGet(storage, KEY_COLLAPSED); },
    setCollapsed(value) { safeSet(storage, KEY_COLLAPSED, value); },
    sound() { return safeGet(storage, KEY_SOUND); },
    hasSound() { return safeHas(storage, KEY_SOUND); },
    setSound(value) { safeSet(storage, KEY_SOUND, value); },
  };
}
