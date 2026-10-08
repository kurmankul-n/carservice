import { test, expect, Page } from '@playwright/test';

// Tests are independent of each other; run on a freshly imported database so order numbers stay predictable.
test.describe.configure({ mode: 'serial' });

const shot = (page: Page, name: string) =>
  page.screenshot({ path: `evidence/${name}.png`, fullPage: true });

// Prints the visible alert/summary text so the observed result can be copied into the report.
const record = async (page: Page, tc: string) =>
  console.log(`[${tc}] ` + (await page.locator('body').innerText()).replace(/\s+/g, ' ').slice(0, 700));

// Opens the booking form and fills it. Service 1 = Oil Change ($35.00), mechanic 1 = Arman Bekov.
async function fillBooking(page: Page, name: string, date: string) {
  await page.goto('/order.php');
  await page.locator('#customer_name').fill(name);
  await page.locator('#service_id').selectOption('1');
  await page.locator('#mechanic_id').selectOption('1');
  await page.locator('#order_date').fill(date);
}

// Clicks Confirm Booking and returns the HTTP status of the answer.
async function submitBooking(page: Page) {
  const [resp] = await Promise.all([
    page.waitForResponse((r) => r.url().includes('/order.php') && r.request().method() === 'POST'),
    page.getByRole('button', { name: 'Confirm Booking' }).click(),
  ]);
  return resp.status();
}

async function book(page: Page, name: string, date: string) {
  await fillBooking(page, name, date);
  return submitBooking(page);
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

test('TC01 - Normal: sort services by price', async ({ page }) => {
  await page.goto('/services.php');
  await page.locator('.sort-bar select').selectOption('price');
  await expect(page).toHaveURL(/sort=price/);
  const prices = await page.locator('.price-tag').allInnerTexts();
  const nums = prices.map((p) => parseFloat(p.replace('$', '')));
  expect(nums).toEqual([...nums].sort((a, b) => a - b));
  await expect(page.locator('.stats-mini')).toContainText('$18.00');
  await expect(page.locator('.stats-mini')).toContainText('$45.00');
  await expect(page.locator('.stats-mini')).toContainText('$29.38');
  await record(page, 'TC01');
  await shot(page, 'TC01_services_by_price');
});

test('TC02 - Normal: book a service for tomorrow', async ({ page }) => {
  const tomorrow = shift(await serverToday(page), 1);
  await book(page, 'Aliya Normal', tomorrow);
  await expect(page.locator('.alert-success')).toContainText('Your booking is confirmed! Order #');
  await expect(page.locator('.summary-total')).toContainText('$44.20');
  await record(page, 'TC02');
  await shot(page, 'TC02_booking_tomorrow');
});

test('TC03 - Borderline: book for today', async ({ page }) => {
  const today = await serverToday(page);
  await book(page, 'Berik Borderline', today);
  await expect(page.locator('.alert-success')).toContainText('Your booking is confirmed!');
  await expect(page.locator('.summary-box')).toContainText('Same-day surcharge (20%)');
  await expect(page.locator('.summary-total')).toContainText('$51.20');
  await record(page, 'TC03');
  await shot(page, 'TC03_booking_today');
});

test('TC04 - Invalid: book for yesterday', async ({ page }) => {
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
  console.log('[TC04 client] ' + clientMsg);
  await shot(page, 'TC04_client_validation');
  // Layer 2: bypass browser validation to reach the server check (no source change).
  await page.locator('form[action="order.php"]').evaluate((f: HTMLFormElement) => { f.noValidate = true; });
  await page.getByRole('button', { name: 'Confirm Booking' }).click();
  await expect(page.locator('.alert-error')).toContainText('Please select a future date.');
  await record(page, 'TC04');
  await shot(page, 'TC04_past_date_rejected');
});

test('TC05 - Invalid: name made of spaces', async ({ page }) => {
  const tomorrow = shift(await serverToday(page), 1);
  await book(page, '     ', tomorrow);
  await expect(page.locator('.alert-error')).toContainText('Please enter your name.');
  await record(page, 'TC05');
  await shot(page, 'TC05_blank_name');
});

test('TC06 - Borderline: 100-character name', async ({ page }) => {
  const tomorrow = shift(await serverToday(page), 1);
  await book(page, 'A'.repeat(100), tomorrow);
  await expect(page.locator('.alert-success')).toContainText('Your booking is confirmed!');
  await record(page, 'TC06');
  await shot(page, 'TC06_name_100');
});

test('TC07 - Invalid: 101-character name', async ({ page }) => {
  test.fail(true, 'Defect: order.php has no length check; MySQL rejects the INSERT at line 84 and the uncaught exception ends the page');
  const tomorrow = shift(await serverToday(page), 1);
  await fillBooking(page, 'B'.repeat(101), tomorrow);
  await shot(page, 'TC07_form_filled');
  const status = await submitBooking(page);
  console.log('[TC07 status] ' + status);
  await record(page, 'TC07');
  await shot(page, 'TC07_name_101');
  await expect(page.locator('.alert-error')).toBeVisible({ timeout: 2000 });
});

test('TC08 - Normal: name with an apostrophe', async ({ page }) => {
  test.fail(true, 'Defect: order.php line 81 puts the name into SQL unescaped; the apostrophe breaks the query');
  const tomorrow = shift(await serverToday(page), 1);
  await fillBooking(page, "Dana O'Connor", tomorrow);
  await shot(page, 'TC08_form_filled');
  const status = await submitBooking(page);
  console.log('[TC08 status] ' + status);
  await record(page, 'TC08');
  await shot(page, 'TC08_name_apostrophe');
  await expect(page.locator('.alert-success')).toContainText('Your booking is confirmed!', { timeout: 2000 });
});

test('TC09 - Borderline: search the lowest order number', async ({ page }) => {
  test.fail(true, 'Defect: orders.php line 63 compares the text order_id from MySQL with === against an int, so no order is ever found');
  await page.goto('/orders.php');
  await page.locator('input[name="search"]').fill('1');
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page).toHaveURL(/search=1/);
  await record(page, 'TC09');
  await shot(page, 'TC09_search_order1');
  await expect(page.locator('.found-card')).toContainText('Order #1 found', { timeout: 2000 });
});

test('TC10 - Invalid: search an order that does not exist', async ({ page }) => {
  await page.goto('/orders.php');
  await page.locator('input[name="search"]').fill('999');
  await page.getByRole('button', { name: 'Search' }).click();
  await expect(page.locator('.alert-error')).toContainText('Order #999 not found.');
  await record(page, 'TC10');
  await shot(page, 'TC10_search_order999');
});
