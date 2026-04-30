// Final verification: does the fixed HTML render cleanly (no leaked source)
// and does Generate produce the full prompt?
const puppeteer = require('puppeteer-core');

(async () => {
  const browser = await puppeteer.launch({
    executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    headless: 'new',
    args: ['--no-sandbox', '--allow-file-access-from-files']
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1280, height: 900 });

  const errors = [];
  page.on('pageerror', e => errors.push('ERR: ' + e.message));
  page.on('console', msg => { if (msg.type() === 'error') errors.push('CONSOLE: ' + msg.text()); });

  await page.goto('file:///c:/laragon/www/widgetprompter.html', { waitUntil: 'domcontentloaded' });
  await new Promise(r => setTimeout(r, 400));

  const before = await page.evaluate(() => ({
    bodyText: document.body.innerText,
    hasCss: typeof window.__WP_WIDGET_CSS__ === 'string' && window.__WP_WIDGET_CSS__.length,
    hasJs:  typeof window.__WP_WIDGET_JS__  === 'string' && window.__WP_WIDGET_JS__.length
  }));
  console.log('BEFORE click:');
  console.log('  body innerText len:', before.bodyText.length);
  console.log('  window.__WP_WIDGET_CSS__ len:', before.hasCss);
  console.log('  window.__WP_WIDGET_JS__  len:', before.hasJs);
  console.log('  last 200:', JSON.stringify(before.bodyText.substring(Math.max(0, before.bodyText.length - 200))));

  // Click Generate
  await page.click('#wpGenerate');
  await new Promise(r => setTimeout(r, 400));

  const after = await page.evaluate(() => ({
    modalOpen: document.getElementById('wpModal').classList.contains('is-open'),
    promptLen: document.getElementById('wpPromptOutput').value.length,
    mainText: document.querySelector('main.wp-main').innerText
  }));
  console.log('\nAFTER click Generate:');
  console.log('  modal is-open:', after.modalOpen);
  console.log('  prompt textarea len:', after.promptLen);
  console.log('  main.wp-main innerText len:', after.mainText.length);

  console.log('\nERRORS:', errors.length);
  errors.forEach(e => console.log('  ' + e));

  await page.screenshot({ path: 'c:/laragon/www/_final.png', fullPage: true });
  await browser.close();
})().catch(e => { console.error(e); process.exit(1); });
