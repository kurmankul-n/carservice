# AutoCall Mobile Car Service: Testing Documentation

**Assignment:** Project Testing (SAU2)
**System:** AutoCall, a mobile car service web application (PHP, MySQL, HTML/CSS, JavaScript)
**Tested on:** 08/10/2026
**Evidence:** screenshots in `evidence/`, one or two per test

## 1. Purpose of this document

This document is the test plan and the test report for AutoCall. It contains **10 test cases**. Each test uses one type of test data (normal, borderline or invalid) and one type of validation (range, presence, length, lookup, special-character or calculation check).

Result: **7 PASS, 3 FAIL.** Section 8 lists the defects the failed tests found.

## 2. System under test

| Page | What it does |
|---|---|
| `index.php` | Home page with counts of services, mechanics and completed jobs |
| `services.php` | List of services, bubble sort by name, price or duration, price statistics |
| `order.php` | Booking form: name, service, mechanic, date. Price = service + $5.00 call-out + 12% VAT, plus 20% for a same-day booking |
| `orders.php` | All orders with their service and mechanic, totals, and a search by order number |
| `mechanics.php` | Mechanics with order counts, earnings and average rating |

The database `car_service_db` (`database.sql`) holds 8 services, 4 mechanics and 8 sample orders. The tests cover the three functions where a user types data or where the app calculates: the services page, the booking form and the order search.

Limits found in the application:

- Name: must not be empty after spaces are removed; the database column holds 100 characters.
- Date: today or later (the browser blocks earlier dates, and the server checks them too).
- Order search: whole numbers from 1.
- Sort: `name`, `price` or `duration`.

## 3. Test data types

- **Normal:** data the program expects. It runs without errors. Tests 1, 2 and 8.
- **Borderline (extreme):** data at the edge of what the program accepts, for example today's date or a 100-character name. It still runs without errors. Tests 3, 6 and 9.
- **Invalid:** data the program must refuse with an error message, because it falls outside the limits or is empty or missing. Tests 4, 5, 7 and 10.

## 4. Types of validation used

Validation means the program checks data before it uses it. The tests exercise these checks:

| Type of validation | What it checks | Where in AutoCall | Tests |
|---|---|---|---|
| Range check | A value lies between allowed limits | Booking date must be today or later (`order.php`, lines 68–75; `min` attribute on the date field) | 3, 4 |
| Presence check | A required value was entered | Name must not be empty (`order.php`, line 38) | 5 |
| Length check | A text is not longer than allowed | Name column holds 100 characters (`database.sql`) | 6, 7 |
| Lookup check | A record exists | Order search finds an order or says “not found” (`orders.php`, lines 57–71) | 9, 10 |
| Special-character check | Characters like an apostrophe do not break the program | Name text goes into the database (`order.php`, lines 81–84) | 8 |
| Calculation check | A computed value is correct | Price, VAT, surcharge, average and sort order | 1, 2, 3 |

## 5. Types of testing used in this project

**1. Black box testing (all 10 tests).** The tester uses the web pages like a customer: types the test data, clicks, and compares what the page shows with the expected result. The tester does not look at the code during the test. Advantage: no knowledge of the code is needed, and it is fast. Disadvantage: when a test fails, it does not say why. Tests 7, 8 and 9 failed, and the page alone did not tell the cause.

**2. White box testing (used to find the cause of the 3 failures).** After the failures, the code was read to find the exact lines. The tester followed the data through the code by hand:

- Test 9: the code compares `$sorted[$mid]['order_id'] === $search_id`. MySQL returns the order number as text (`"1"`) and the form value is a number (`1`). A strict check never treats them as equal.
- Test 8: the name goes straight into the database command at `order.php`, line 81, so an apostrophe ends the text too early.
- Test 7: no length check exists before the insert at line 84.

White box testing here was limited to this diagnosis. The project has no full white box test with trace tables for every code path. That would take much longer.

## 6. Environment and run order

- Fedora Linux, PHP 8.4 built-in web server (`php -S localhost:8081 -d display_errors=1`), MySQL, Chromium. The `display_errors=1` setting is the default in XAMPP, so a crash shows the PHP error text on the page.
- Playwright, a browser automation tool, ran the tests and took the screenshots (`playwright/tests/carservice.spec.ts`). The database was loaded fresh from `database.sql` before the run.
- The tests do not depend on each other. Bookings made by Tests 2, 3 and 6 get order numbers #9, #10 and #11.
- Test 4 switches off the browser's date check with a script, so the server's own check gets tested too. The app code was not changed.
- Tests 7, 8 and 9 are marked as known failures in Playwright, because the app does not do what the test expects.

## 7. Results table

| # | Purpose | Description | Type of validation | Test data | Expected result | Actual result / Evidence |
|---:|---|---|---|---|---|---|
| 1 | Check that the services page sorts by price and calculates price statistics. | 1. Open `/services.php`.<br>2. Choose **Price (Low–High)** in Sort by.<br>3. Read the order of the cards and the four statistic boxes. | Calculation check and sort-order check | Normal: sort option `price`; sample prices $18, $20, $22, $25, $30, $35, $40, $45 (8 services, $235 in total) | Cards go from $18.00 to $45.00. Statistics: cheapest $18.00, most expensive $45.00, average $29.38, 8 services. | The address changed to `services.php?sort=price`. The cards went from Air Filter Replacement ($18.00) to Spark Plug Replacement ($45.00), from cheapest to most expensive. The boxes showed $18.00, $45.00, $29.38 and 8. **PASS.** Evidence: `evidence/TC01_services_by_price.png` |
| 2 | Check that a customer can book a service for a future date and gets the right price. | Before: load a fresh copy of the database.<br>1. Open `/order.php`.<br>2. Enter name `Aliya Normal`, select Oil Change and mechanic Arman Bekov.<br>3. Set Preferred Date to tomorrow (`2026-10-09`).<br>4. Click **Confirm Booking**. | Calculation check (service price + call-out fee + VAT) | Normal: name `Aliya Normal`; service Oil Change ($35.00); mechanic Arman Bekov; date `2026-10-09` (tomorrow). Expected price: $35.00 + $5.00 + 12% VAT ($4.20) = $44.20 | The app shows “Your booking is confirmed! Order #9” and an Order Summary with total $44.20. | The alert said “Your booking is confirmed! Order #9”. The Order Summary listed Oil Change $35.00, call-out fee $5.00, VAT $4.20, duration 30 min and Total $44.20. **PASS.** Evidence: `evidence/TC02_booking_tomorrow.png` |
| 3 | Check that today, the earliest allowed date, is accepted with the same-day surcharge. | 1. Open `/order.php`.<br>2. Enter name `Berik Borderline`, select Oil Change and Arman Bekov.<br>3. Set Preferred Date to today (`2026-10-08`).<br>4. Click **Confirm Booking**. | Range check (date): earliest allowed date | Borderline: date `2026-10-08` (today, the first date the form accepts); service Oil Change ($35.00). Expected surcharge: 20% of $35.00 = $7.00 | The app accepts the booking and adds “Same-day surcharge (20%) +$7.00”, so the total is $51.20. | The alert said “Your booking is confirmed! Order #10”. The summary showed Same-day surcharge (20%) +$7.00 and Total $51.20. **PASS.** Evidence: `evidence/TC03_booking_today.png` |
| 4 | Check that the app refuses a booking date in the past. | 1. Open `/order.php`.<br>2. Enter name `Ivan Invalid`, select Oil Change and Arman Bekov.<br>3. Set Preferred Date to yesterday (`2026-10-07`) and click **Confirm Booking**. The browser stops the form first.<br>4. Switch off the browser check (the test does this with a script; the app code stays the same) and send the form again, so the server check is tested too. | Range check (date): dates before today must be refused, by the browser and by the server | Invalid: date `2026-10-07` (one day before the earliest allowed date) | The app refuses the booking with “Please select a future date.” and saves no order. | The browser showed the warning “Value must be 08.10.2026 or later.” (`evidence/TC04_client_validation.png`). With the browser check off, the server showed “Please select a future date.” and no Order Summary. No order was saved. **PASS.** Evidence: `evidence/TC04_past_date_rejected.png`, `evidence/TC04_client_validation.png` |
| 5 | Check that a name made only of spaces is refused. | 1. Open `/order.php`.<br>2. Type five spaces in Your Full Name.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Presence check (the name must not be empty) | Invalid: name made of 5 spaces (the browser accepts it because the field is not empty; the server removes the spaces and finds nothing left) | The app refuses the booking with “Please enter your name.” | The page showed “Please enter your name.” and no Order Summary. No order was saved. **PASS.** Evidence: `evidence/TC05_blank_name.png` |
| 6 | Check that the longest name the database can store is accepted. | 1. Open `/order.php`.<br>2. Enter a name of 100 letters `A`.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Length check (maximum 100 characters) | Borderline: name `AAAA…A` (100 characters, the most the database can store) | The app accepts the booking and shows a confirmation with total $44.20. | The alert said “Your booking is confirmed! Order #11” with Total $44.20. **PASS.** Evidence: `evidence/TC06_name_100.png` |
| 7 | Check that a name longer than the database allows is refused with a message. | 1. Open `/order.php`.<br>2. Enter a name of 101 letters `B`.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Length check (more than 100 characters must be refused) | Invalid: name `BBBB…B` (101 characters, one more than the database can store) | The app refuses the booking and shows an error message on the booking page. No order is saved. | The booking page was replaced by a raw PHP error page: “Fatal error: Uncaught mysqli_sql_exception: Data too long for column 'customer_name' at row 1 in …/order.php:84”. The user gets no friendly message, and the page shows the file path. No order was saved (a database check afterwards found 11 orders and none with this name). **FAIL.** Defect: `order.php` never checks the name length. The database refuses the long name, and the page crashes instead of showing an error. Evidence: `evidence/TC07_form_filled.png`, `evidence/TC07_name_101.png` |
| 8 | Check that a normal name with an apostrophe can be booked. | 1. Open `/order.php`.<br>2. Enter name `Dana O'Connor`.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Special-character check (the app must handle an apostrophe in text) | Normal: name `Dana O'Connor` (a real surname with an apostrophe) | The app accepts the booking and shows “Your booking is confirmed!” with total $44.20. | The booking page was replaced by a raw PHP error page: “Fatal error: Uncaught mysqli_sql_exception: You have an error in your SQL syntax … near 'Connor', 1, 1, '2026-10-09', 'Pending')' … in …/order.php:84”. The page shows part of the database command. No order was saved. **FAIL.** Defect: `order.php` (line 81) pastes the name straight into the database command. The apostrophe ends the name too early and breaks the command. An attacker could use the same gap to run their own database commands (SQL injection). Evidence: `evidence/TC08_form_filled.png`, `evidence/TC08_name_apostrophe.png` |
| 9 | Check that the order search finds the lowest order number. | 1. Open `/orders.php`.<br>2. Enter `1` in Binary search by Order #.<br>3. Click **Search**. | Lookup check (an existing record must be found) | Borderline: order # `1` (the lowest order number that exists and the lowest the field accepts) | The app shows a card “Order #1 found” with Marat Usenov, Oil Change, Arman Bekov. | The page showed “Order #1 not found.”, yet order 1 (Marat Usenov) is in the table below it. **FAIL.** Defect: `orders.php` (line 63) compares the order number from the database, stored as text, with the number typed in, using a strict check (`===`). The strict check never matches text with a number, so the search never finds any order. Evidence: `evidence/TC09_search_order1.png` |
| 10 | Check that searching for an order that does not exist shows a message. | 1. Open `/orders.php`.<br>2. Enter `999` in Binary search by Order #.<br>3. Click **Search**. | Lookup check (a missing record must give a clear message) | Invalid: order # `999` (no such order) | The app shows “Order #999 not found.” and keeps the order table visible. | The page showed “Order #999 not found.” above the full order table. **PASS.** Note: because of the defect in Test 9, the search shows this message for every number. This test checks only the message. Evidence: `evidence/TC10_search_order999.png` |

## 8. Defects found

| Test | Severity | Problem | Where | Suggested fix |
|---:|---|---|---|---|
| 8 | High | A name with an apostrophe, such as `O'Connor`, crashes the booking page. The app pastes the name straight into the database command, so an attacker could also run their own commands (SQL injection). | `order.php`, lines 81–84 | Use a prepared statement for the insert. |
| 9 | High | The order search never finds an order. The order number from the database is text, and the strict check (`===`) compares it with a number. | `orders.php`, line 63 | Convert the order number with `(int)` before comparing. |
| 7 | Medium | A name longer than 100 characters crashes the booking page instead of showing a message. | `order.php`, lines 38–49 and 84 | Check the length before saving and add `maxlength="100"` to the name field. |
| 7, 8 | Medium | A crash shows the user a raw PHP error with the file path and part of the database command (Tests 7 and 8). The code at line 101 also prints the raw database error when saving fails. This tells an attacker about the table and its columns. | `order.php`, lines 84 and 101 | Show a general message and write the details to the server log. Turn `display_errors` off on a live site. |

## 9. Completion checklist

- [x] MySQL and the web server were running.
- [x] The database was re-imported from `database.sql` before the run.
- [x] All 10 tests were executed.
- [x] Normal, borderline and invalid test data were included (3 normal, 3 borderline, 4 invalid).
- [x] Each test names a type of validation (section 4).
- [x] Two types of testing are explained (section 5).
- [x] Every Actual Result is based on observation.
- [x] Every test has PASS or FAIL.
- [x] Every test has at least one clear screenshot.
- [x] Every failed test has a defect note (section 8).
