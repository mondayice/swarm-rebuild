/**
 * Visitor preferences, all site-wide (no per-URL keys). Storage is injected so
 * tests run against a memory map and private-mode Storage that throws on every
 * access degrades to defaults without ever breaking the caller.
 */

const KEY_SEEN = 'srIntroSeen';
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

function safeSet(storage, key, value) {
  try {
    if (value) {
      storage.setItem(key, '1');
    } else {
      storage.removeItem(key);
    }
  } catch {
    /* private mode: preference simply doesn't persist */
  }
}

export function createPrefs(storage) {
  return {
    seen() { return safeGet(storage, KEY_SEEN); },
    markSeen() { safeSet(storage, KEY_SEEN, true); },
    optedOut() { return safeGet(storage, KEY_OPT_OUT); },
    setOptedOut(value) { safeSet(storage, KEY_OPT_OUT, value); },
    collapsed() { return safeGet(storage, KEY_COLLAPSED); },
    setCollapsed(value) { safeSet(storage, KEY_COLLAPSED, value); },
    sound() { return safeGet(storage, KEY_SOUND); },
    setSound(value) { safeSet(storage, KEY_SOUND, value); },
  };
}
