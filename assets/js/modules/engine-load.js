/**
 * One-shot injector for the engine <script>. Injects at most once; both onload
 * and onerror resolve (true/false) — a load() that rejects could break the host
 * page (stale page cache after an update serving a loader whose engine 404s),
 * so failures stay silent and terminal: later load() calls resolve false
 * without re-injecting.
 */

export function createEngineLoader(doc, src) {
  let injected = false;
  let loaded = false;
  let pending = null;

  function load() {
    if (injected) {
      return pending;
    }
    injected = true;
    pending = new Promise((resolve) => {
      const script = doc.createElement('script');
      script.src = src;
      script.onload = () => {
        loaded = true;
        resolve(true);
      };
      script.onerror = () => {
        resolve(false);
      };
      doc.head.appendChild(script);
    });
    return pending;
  }

  function isLoaded() {
    return loaded;
  }

  return { load, isLoaded };
}
