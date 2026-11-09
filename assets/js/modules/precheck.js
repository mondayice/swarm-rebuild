/**
 * Pre-erase over-limit heuristic. First line of defense BEFORE the engine is
 * even loaded: the engine's erase happens inside its start(), so by the time
 * its own 4000-layer cap truncates, the visitor has already lost the page.
 * Defaults mirror the engine's world clamp (40000px) and a DOM-node budget.
 */

export function precheckOk({ scrollHeight, elementCount, maxHeight = 40000, maxElements = 6000 }) {
  if (!(Number.isFinite(scrollHeight) && Number.isFinite(elementCount))) {
    return false;
  }
  return scrollHeight <= maxHeight && elementCount <= maxElements;
}
