import { test, expect, Page } from '@playwright/test';

// Order matters: bookings in TC05/TC06/TC09 change the Orders and Mechanics pages checked in TC12–TC15.
test.describe.configure({ mode: 'serial' });

const shot = (page: Page, name: string) =>
  page.screenshot({ path: `evidence/${name}.png`, fullPage: true });

// Prints the visible alert/summary text so the observed result can be copied into the report.
const record = async (page: Page, tc: string) =>
  console.log(`[${tc}] ` + (await page.locator('body').innerText()).replace(/\s+/g, ' ').slice(0, 700));

// Fills the booking form. Service 1 = Oil Change ($35.00), mechanic 1 = Arman Bekov.
async function book(page: Page, name: string, date: string | null, bypassBrowserChecks = false) {
  await page.goto('/order.php');
  await page.locator('#customer_name').fill(name);
  await page.locator('#service_id').selectOption('1');
  await page.locator('#mechanic_id').selectOption('1');
  if (date !== null) await page.locator('#order_date').fill(date);
  if (bypassBrowserChecks) await page.locator('form[action="order.php"]').evaluate((f: HTMLFormElement) => { f.noValidate = true; });
  const [resp] = await Promise.all([
    page.waitForResponse((r) => r.url().includes('/order.php') && r.request().method() === 'POST'),
    page.getByRole('button', { name: 'Confirm Booking' }).click(),
  ]);
  return resp.status();
}

// "Today" as the server sees it: order.php writes it into the date field's min attribute.
async function serverToday(page: Page) {
  await page.goto('/order.php');
  return (await page.locator('#order_date').getAttribute('min')) ?? '';
}
const shift = (iso: string, days: number) => {
  const d = new Date(iso + 'T00:00:00Z');
  d.setUTCDate(d.getUTCDate() + days);
  return d.toISOString().slice(0, 10);
};

test('TC01 - Normal: home page statistics', async ({ page }) => {
  await page.goto('/index.php');
  const stats = page.locator('.stat-card');
  await expect(stats.nth(0)).toContainText('8');
  await expect(stats.nth(1)).toContainText('4');
  await expect(stats.nth(2)).toContainText('4+');
  await record(page, 'TC01');
  await shot(page, 'TC01_home_stats');
});

test('TC02 - Normal: sort services by price', async ({ page }) => {
  await page.goto('/services.php');
  await page.locator('.sort-bar select').selectOption('price');
  await expect(page).toHaveURL(/sort=price/);
  const prices = await page.locator('.price-tag').allInnerTexts();
  const nums = prices.map((p) => parseFloat(p.replace('$', '')));
  expect(nums).toEqual([...nums].sort((a, b) => a - b));
  await expect(page.locator('.stats-mini')).toContainText('$18.00');
  await expect(page.locator('.stats-mini')).toContainText('$45.00');
  await expect(page.locator('.stats-mini')).toContainText('$29.38');
  await record(page, 'TC02');
  await shot(page, 'TC02_services_by_price');
});

test('TC03 - Invalid: unknown sort value', async ({ page }) => {
  await page.goto('/services.php?sort=abc');
  const names = await page.locator('.card-title').allInnerTexts();
  expect(names).toEqual([...names].sort());
  await record(page, 'TC03');
  await shot(page, 'TC03_sort_invalid');
});

test('TC04 - Normal: live price estimate', async ({ page }) => {
  await page.goto('/order.php');
  await page.locator('#service_id').selectOption('1');
  await expect(page.locator('#price-preview')).toBeVisible();
  await expect(page.locator('#estimated-total')).toHaveText('$44.20');
  await shot(page, 'TC04_live_estimate');
});

test('TC05 - Normal: book a service for tomorrow', async ({ page }) => {
  const tomorrow = shift(await serverToday(page), 1);
  await book(page, 'Aliya Normal', tomorrow);
  await expect(page.locator('.alert-success')).toContainText('Your booking is confirmed! Order #');
  await expect(page.locator('.summary-total')).toContainText('$44.20');
  await record(page, 'TC05');
  await shot(page, 'TC05_booking_tomorrow');
});

test('TC06 - Borderline: book for today', async ({ page }) => {
  const today = await serverToday(page);
  await book(page, 'Berik Borderline', today);
  await expect(page.locator('.alert-success')).toContainText('Your booking is confirmed!');
  await expect(page.locator('.summary-box')).toContainText('Same-day surcharge (20%)');
  await expect(page.locator('.summary-total')).toContainText('$51.20');
  await record(page, 'TC06');
  await shot(page, 'TC06_booking_today');
});

test('TC07 - Invalid: book for yesterday', async ({ page }) => {
  const yesterday = shift(await serverToday(page), -1);
  await page.goto('/order.php');
  await page.locator('#customer_name').fill('Ivan Invalid');
  await page.locator('#service_id').selectOption('1');
  await page.locator('#mechanic_id').selectOption('1');
  await page.locator('#order_date').fill(yesterday);
  // Layer 1: the date field has min=today, so the browser blocks the form.
  await page.getByRole('button', { name: 'Confirm Booking' }).click();
  await page.waitForTimeout(400);
  const clientMsg = await page.locator('#order_date').evaluate((el: HTMLInputElement) => el.validationMessage);
  console.log('[TC07 client] ' + clientMsg);
  await shot(page, 'TC07_client_validation');
  // Layer 2: bypass browser validation to reach the server check (no source change).
  await page.locator('form[action="order.php"]').evaluate((f: HTMLFormElement) => { f.noValidate = true; });
  await page.getByRole('button', { name: 'Confirm Booking' }).click();
  await expect(page.locator('.alert-error')).toContainText('Please select a future date.');
  await record(page, 'TC07');
  await shot(page, 'TC07_past_date_rejected');
});

test('TC08 - Invalid: name made of spaces', async ({ page }) => {
  const tomorrow = shift(await serverToday(page), 1);
  await book(page, '     ', tomorrow);
  await expect(page.locator('.alert-error')).toContainText('Please enter your name.');
  await record(page, 'TC08');
  await shot(page, 'TC08_blank_name');
});

test('TC09 - Borderline: 100-character name', async ({ page }) => {
  const tomorrow = shift(await serverToday(page), 1);
  await book(page, 'A'.repeat(100), tomorrow);
  await record(page, 'TC09');
  await shot(page, 'TC09_name_100');
});

test('TC10 - Invalid: 101-character name', async ({ page }) => {
  test.fail(true, 'Defect: order.php:84 has no length check; MySQL rejects the INSERT and the page dies with HTTP 500');
  const tomorrow = shift(await serverToday(page), 1);
  const status = await book(page, 'B'.repeat(101), tomorrow);
  console.log('[TC10 status] ' + status);
  await record(page, 'TC10');
  await shot(page, 'TC10_name_101');
  await expect(page.locator('.alert-error')).toBeVisible({ timeout: 2000 });
});

test("TC11 - Normal: name with an apostrophe", async ({ page }) => {
  test.fail(true, 'Defect: order.php:81 puts the name into SQL unescaped; the apostrophe breaks the query (HTTP 500)');
  const tomorrow = shift(await serverToday(page), 1);
  const status = await book(page, "Dana O'Connor", tomorrow);
  console.log('[TC11 status] ' + status);
  await record(page, 'TC11');
  await shot(page, 'TC11_name_apostrophe');
  await expect(page.locator('.alert-success')).toContainText('Your booking is confirmed!', { timeout: 2000 });
});

test('TC12 - Normal: orders list and totals', async ({ page }) => {
  await page.goto('/orders.php');
  await expect(page.locator('.data-table tbody tr').first()).toBeVisible();
  console.log('[TC12 rows] ' + (await page.locator('.data-table tbody tr').count()));
  await record(page, 'TC12');
  await shot(page, 'TC12_orders_list');
});

test('TC13 - Borderline: search the lowest order number', async ({ page }) => {
  test.fail(true, "Defect: orders.php:63 compares the string order_id from MySQL with === against an int, so no order is ever found");
  await page.goto('/orders.php');
  await page.locator('input[name="search"]').fill('1');
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page).toHaveURL(/search=1/);
  await record(page, 'TC13');
  await shot(page, 'TC13_search_order1');
  await expect(page.locator('.found-card')).toContainText('Order #1 found', { timeout: 2000 });
});

test('TC14 - Invalid: search an order that does not exist', async ({ page }) => {
  await page.goto('/orders.php');
  await page.locator('input[name="search"]').fill('999');
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page.locator('.alert-error')).toContainText('Order #999 not found.');
  await record(page, 'TC14');
  await shot(page, 'TC14_search_order999');
});

test('TC15 - Normal: mechanics overview', async ({ page }) => {
  await page.goto('/mechanics.php');
  await expect(page.locator('body')).toContainText('4 mechanics in total');
  await record(page, 'TC15');
  await shot(page, 'TC15_mechanics');
});
