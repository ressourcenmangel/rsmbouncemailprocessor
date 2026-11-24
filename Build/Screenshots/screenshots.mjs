// Creates the backend screenshots of the documentation (TYPO3 14.3, light theme, 1440x900).
// Runs inside the Playwright container, see README.md in this directory.
import { chromium } from 'playwright';

const BASE = process.env.BASE_URL ?? 'https://v14.rsmbouncemailprocessor.ddev.site';
const USER = process.env.BE_USER ?? 'admin';
const PASSWORD = process.env.BE_PASSWORD ?? 'Password:joh316';
const OUT = process.env.OUT_DIR ?? '/out';
const NEWSLETTER_PAGE = process.env.NEWSLETTER_PAGE ?? '2';

const browser = await chromium.launch();
const context = await browser.newContext({
  viewport: { width: 1440, height: 900 },
  colorScheme: 'light',
  ignoreHTTPSErrors: true,
  locale: 'en-US',
});
const page = await context.newPage();

// login with the form
await page.goto(`${BASE}/typo3/`);
await page.fill('#t3-username', USER);
await page.fill('#t3-password', PASSWORD);
await page.click('button[type="submit"]');
await page.waitForSelector('.scaffold-content-module-iframe, iframe#typo3-contentIframe', { timeout: 30000 });

const shot = async (name) => {
  await page.waitForTimeout(1500);
  await page.screenshot({ path: `${OUT}/${name}.png` });
  console.log(`${name}.png`);
};

// bounce reports
await page.goto(`${BASE}/typo3/module/web/rsmbouncemailprocessor?id=${NEWSLETTER_PAGE}`);
await shot('bounce-reports');

// recipient reports: the second tab of the module
const frame = page.frameLocator('iframe').first();
await frame.getByRole('link', { name: 'Recipient Report' }).first().click();
await shot('recipient-reports');

// scheduler: edit form of the analyze task (the password field is empty)
await page.goto(`${BASE}/typo3/module/scheduler`);
await frame.locator('a[title="Edit"], a[data-bs-toggle="tooltip"][href*="edit"]').first().click();
// scroll down to the task settings, below the task header
await frame.getByText('Password', { exact: true }).first().scrollIntoViewIfNeeded();
await shot('scheduler-analyze-task');

await browser.close();
