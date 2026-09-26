'use strict';

const fs = require('fs');
const path = require('path');
const puppeteer = require('puppeteer-core');

const base = (process.env.KLASSCI_E2E_BASE_URL || 'https://presentation.klassci.com').replace(/\/$/, '');
const username = process.env.KLASSCI_E2E_USERNAME;
const password = process.env.KLASSCI_E2E_PASSWORD;
const runAi = process.env.KLASSCI_E2E_RUN_AI === '1';
const output = process.env.KLASSCI_E2E_SCREENSHOTS || path.resolve('artifacts/messages-qa');

if (!username || !password) {
  console.error('KLASSCI_E2E_USERNAME and KLASSCI_E2E_PASSWORD are required for presentation QA.');
  process.exit(2);
}

function browserPath() {
  const candidates = [
    process.env.CHROME_PATH,
    '/usr/bin/google-chrome',
    '/usr/bin/google-chrome-stable',
    '/usr/bin/chromium',
    '/usr/bin/chromium-browser',
  ].filter(Boolean);
  const found = candidates.find(p => fs.existsSync(p));
  if (!found) throw new Error('Chrome/Chromium executable not found; set CHROME_PATH.');
  return found;
}

function assert(condition, message) {
  if (!condition) throw new Error('ASSERTION FAILED: ' + message);
}

async function text(page, selector) {
  return page.$eval(selector, el => el.textContent.replace(/\s+/g, ' ').trim());
}

async function shot(page, name) {
  fs.mkdirSync(output, {recursive: true});
  await page.screenshot({path: path.join(output, name), fullPage: true});
}

async function login(page) {
  await page.goto(base + '/login', {waitUntil: 'networkidle2'});
  await page.type('input[name="username"]', username);
  await page.type('input[name="password"]', password);
  await Promise.all([
    page.waitForNavigation({waitUntil: 'networkidle2'}),
    page.click('form button[type="submit"], form input[type="submit"]'),
  ]);
  assert(!page.url().includes('/login'), 'Login did not leave /login');
}

async function openLosseni(page) {
  await page.goto(base + '/messages', {waitUntil: 'networkidle2'});
  await page.waitForSelector('[data-conversation]', {timeout: 15000});
  const found = await page.evaluate(() => {
    const rows = Array.from(document.querySelectorAll('[data-conversation]'));
    const row = rows.find(el => /LOSSENI KABIROU COULIBALY/i.test(el.textContent));
    if (!row) return false;
    row.click();
    return true;
  });
  assert(found, 'LOSSENI KABIROU COULIBALY conversation not found');
  await page.waitForFunction(() => /LOSSENI KABIROU COULIBALY/i.test(document.querySelector('[data-thread-title]')?.textContent || ''), {timeout: 10000});
  await page.click('[data-context-toggle]');
  await page.waitForSelector('[data-context].is-open');
}

async function checkLosseniKipre(page) {
  const context = await text(page, '[data-context-body]');
  const thread = await text(page, '[data-thread]');
  assert(/LOSSENI KABIROU COULIBALY/i.test(context), 'Context must show Losseni as participant');
  assert(/Personnel|Agent administratif|Scolarité/i.test(context), 'Losseni must be identified as staff context');
  assert(/Inscription\s*[—-]\s*KIPRE JEAN/i.test(context + ' ' + thread), 'Linked dossier must explicitly show KIPRE JEAN');
  assert(/Relation à vérifier/i.test(context + ' ' + thread), 'Ambiguous relationship must be explicit');
  assert(!/Dossier KLASSCI/i.test(context + ' ' + thread), 'Fake Dossier KLASSCI placeholder must never appear');
  assert(!/À consulter/i.test(context + ' ' + thread), 'Fake À consulter status must never appear');
  assert(!/Préparer une relance/i.test(context), 'Financial relance must be unavailable while relationship is ambiguous');
}

async function checkFailedOptimisticMessage(page) {
  let blocked = true;
  await page.setRequestInterception(true);
  const intercept = req => {
    if (blocked && req.method() === 'POST' && /\/messages\/conversations\/\d+\/messages/.test(req.url())) req.abort('failed');
    else req.continue();
  };
  page.on('request', intercept);
  const marker = 'QA-NON-ENVOYE-' + Date.now();
  await page.type('[data-compose]', marker);
  await page.click('[data-send]');
  await page.waitForFunction(m => document.body.innerText.includes(m) && document.body.innerText.includes('Échec'), {timeout: 8000}, marker);
  const countBefore = await page.evaluate(m => (document.body.innerText.match(new RegExp(m, 'g')) || []).length, marker);
  assert(countBefore === 1, 'Failed optimistic message must appear once');
  await page.click('[data-retry]');
  await page.waitForTimeout(700);
  const countAfter = await page.evaluate(m => (document.body.innerText.match(new RegExp(m, 'g')) || []).length, marker);
  assert(countAfter === 1, 'Retry failure must not duplicate the optimistic message');
  blocked = false;
  page.off('request', intercept);
  await page.setRequestInterception(false);
}

async function checkActions(page) {
  await page.click('[data-space="actions"]');
  await page.waitForSelector('[data-action-list]');
  const actionText = await text(page, '[data-action-list]');
  assert(!/Aucune action/i.test(actionText), 'Historical workflow actions must be visible in the action center');

  await page.click('[data-new]');
  await page.click('[data-intent-action]');
  await page.waitForSelector('[data-action-form]:not([hidden])');
  const marker = 'QA Vérification ' + Date.now();
  await page.type('[data-action-form] input[name="title"]', marker);
  await page.type('[data-action-form] input[name="subject"]', 'QA navigateur — présentation');
  await page.select('[data-action-form] select[name="priority"]', 'low');
  await page.click('[data-action-submit]');
  await page.waitForFunction(m => document.body.innerText.includes(m), {timeout: 10000}, marker);

  await page.click('[data-action-view="kanban"]');
  await page.waitForSelector('[data-kanban]:not([hidden])');
  const kanban = await text(page, '[data-kanban]');
  assert(kanban.includes(marker), 'Created action must appear in Kanban');
}

async function checkNananAmbiguity(page) {
  if (!runAi) return;
  await page.click('[data-space="inbox"]');
  await openLosseni(page);
  const buttonFound = await page.evaluate(() => {
    const b = Array.from(document.querySelectorAll('[data-ai]')).find(el => /Vérifier le lien/i.test(el.textContent));
    if (!b) return false; b.click(); return true;
  });
  assert(buttonFound, 'Ambiguous Nanan action not found');
  await page.waitForSelector('.ast-panel textarea', {visible: true});
  await page.click('.ast-panel button[type="submit"], .ast-send');
  await page.waitForFunction(() => /Je ne peux pas établir le lien entre cet interlocuteur et ce dossier/i.test(document.body.innerText), {timeout: 60000});
  const body = await page.evaluate(() => document.body.innerText);
  assert(!/Losseni[^\n]{0,80}(n.?a pas payé|doit payer|paiement en retard)/i.test(body), 'Nanan must not attribute KIPRE payment status to Losseni');
}

(async () => {
  const browser = await puppeteer.launch({headless: 'new', executablePath: browserPath(), args: ['--no-sandbox','--disable-dev-shm-usage']});
  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(15000);
    await page.setViewport({width: 1440, height: 1000, deviceScaleFactor: 1});
    await login(page);
    await openLosseni(page);
    await checkLosseniKipre(page);
    await shot(page, '01-losseni-kipre-desktop.png');
    await checkFailedOptimisticMessage(page);

    await checkActions(page);
    await shot(page, '02-action-center-kanban-desktop.png');

    await page.setViewport({width: 820, height: 1100, deviceScaleFactor: 1});
    await page.goto(base + '/messages', {waitUntil: 'networkidle2'});
    await shot(page, '03-messages-tablet.png');

    await page.setViewport({width: 390, height: 844, deviceScaleFactor: 1});
    await page.goto(base + '/messages', {waitUntil: 'networkidle2'});
    await page.waitForSelector('[data-conversation]');
    await shot(page, '04-conversations-mobile.png');
    await openLosseni(page);
    await shot(page, '05-conversation-mobile.png');

    await checkNananAmbiguity(page);
    console.log('Messages presentation QA passed. Screenshots:', output);
  } finally {
    await browser.close();
  }
})().catch(err => { console.error(err.stack || err); process.exit(1); });
