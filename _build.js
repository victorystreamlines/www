// One-time build: extracts text/plain script content, re-embeds as JS string literals.
// This file is temporary — it gets deleted after use. The output .html is fully standalone.

const fs = require('fs');
const path = 'c:/laragon/www/widgetprompter.html';
const html = fs.readFileSync(path, 'utf8');

const cssRe = /<script type="text\/plain" id="wpSrcCss" hidden>([\s\S]*?)<\/script>/;
const jsRe  = /<script type="text\/plain" id="wpSrcJs" hidden>([\s\S]*?)<\/script>/;

const cssM = html.match(cssRe);
const jsM  = html.match(jsRe);
if (!cssM || !jsM) {
  console.error('source blocks not found');
  process.exit(1);
}

const cssContent = cssM[1];
const jsContent  = jsM[1];

console.log('extracted CSS:', cssContent.length, 'chars');
console.log('extracted JS :', jsContent.length, 'chars');

// JSON.stringify gives us a valid JS string literal (with proper escaping).
const cssLit = JSON.stringify(cssContent);
const jsLit  = JSON.stringify(jsContent);

// Build the replacement: a single executable <script> that sets globals.
const replacement =
`<!-- ─────────────────────────────────────────────────────
     EMBEDDED WIDGET SOURCE — stored as JS string literals.
     Stored inside an executable <script> (no type attribute),
     so the content lives in JS memory only — NEVER in the DOM
     as visible text. Safe under every renderer (browser,
     Live Server, IDE preview, source-viewer extensions).
     ───────────────────────────────────────────────────── -->
<script>
window.__WP_WIDGET_CSS__ = ${cssLit};
window.__WP_WIDGET_JS__  = ${jsLit};
</script>`;

// Match the whole wrapper block (our previous defensive wrap) + its two scripts and replace.
const wrapperRe = /<!-- ─+\s*\n\s*EMBEDDED WIDGET SOURCE[\s\S]*?<\/div><!-- \/\.wp-embedded-src -->/;
if (!wrapperRe.test(html)) {
  console.error('wrapper block not found — structure changed?');
  process.exit(1);
}

let out = html.replace(wrapperRe, replacement);

// Update main script to read from window globals instead of DOM textContent.
const oldRead = `  /* Embedded source (read from <script type="text/plain">) */
  var WIDGET_CSS_SRC = document.getElementById('wpSrcCss').textContent;
  var WIDGET_JS_SRC  = document.getElementById('wpSrcJs').textContent;`;
const newRead = `  /* Embedded source (read from pre-set JS globals) */
  var WIDGET_CSS_SRC = window.__WP_WIDGET_CSS__ || '';
  var WIDGET_JS_SRC  = window.__WP_WIDGET_JS__  || '';`;

if (!out.includes(oldRead)) {
  console.error('main-script read block not found');
  process.exit(1);
}
out = out.replace(oldRead, newRead);

// Remove the old defensive CSS rule for script[type] / .wp-embedded-src
// (no longer needed since there's no text/plain script or wrapper anymore).
const oldCss = `  /* ── Ensure embedded-source <script type="text/plain"> blocks
        never render as visible text regardless of UA/extension CSS.
        Belt + suspenders: rule targets both the scripts AND their
        hidden wrapper, so non-standard previews (Live Server, some
        IDE previews, source-viewer extensions) cannot leak the
        widget source onto the page. ── */
  script[type],
  .wp-embedded-src,
  .wp-embedded-src * {
    display: none !important;
    visibility: hidden !important;
    position: absolute !important;
    left: -99999px !important;
    top: -99999px !important;
    width: 0 !important;
    height: 0 !important;
    max-width: 0 !important;
    max-height: 0 !important;
    overflow: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    clip: rect(0 0 0 0) !important;
    clip-path: inset(50%) !important;
    white-space: nowrap !important;
    user-select: none !important;
  }
`;
if (!out.includes(oldCss)) {
  console.error('old defensive CSS block not found');
  process.exit(1);
}
out = out.replace(oldCss, '');

fs.writeFileSync(path, out, 'utf8');
console.log('rewrote', path, '→', out.length, 'bytes');
