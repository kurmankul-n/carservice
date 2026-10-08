# AutoCall Mobile Car Service: Testing Documentation

**Assignment:** Project – Testing (SAU2)
**System:** AutoCall, a mobile car service web application (PHP, MySQL, HTML/CSS, JavaScript)
**Tested on:** 08/10/2026
**Evidence:** screenshots in `evidence/`, one or more per test

## 1. Purpose of this document

This document records the final testing of AutoCall. It contains **15 test cases**. Each test uses one type of test data: normal, borderline (extreme) or invalid. All tests are black box tests: the tester uses the web pages like a customer, types the test data and compares what the page shows with the expected result. The tester does not look at the code during the test.

Result: **12 PASS, 3 FAIL.** Section 6 lists the defects the tests found.

## 2. System under test

| Page | What it does |
|---|---|
| `index.php` | Home page with counts of services, mechanics and completed jobs |
| `services.php` | List of services, bubble sort by name, price or duration, price statistics |
| `order.php` | Booking form: name, service, mechanic, date; price = service + $5.00 call-out + 12% VAT, plus 20% for a same-day booking |
| `orders.php` | All orders with their service and mechanic, totals, and a search by order number |
| `mechanics.php` | Mechanics with order counts, earnings and average rating |

The database `car_service_db` (`database.sql`) holds 8 services, 4 mechanics and 8 sample orders.

Limits found in the application:

- Name: must not be empty after spaces are removed; the database column holds 100 characters.
- Date: today or later (the browser blocks earlier dates, the server checks them too).
- Order search: whole numbers from 1.
- Sort: `name`, `price` or `duration`; anything else falls back to name.

## 3. Test data types

- **Normal:** data the program expects. It runs without errors.
- **Borderline (extreme):** data at the edge of what the program accepts, for example today's date or a 100-character name. It still runs without errors.
- **Invalid:** data the program must reject with an error message, because it has the wrong type, uses characters that are not allowed, or falls outside the limits.

Mix in this plan: 7 normal, 3 borderline, 5 invalid.

## 4. Environment and run order

- Fedora Linux, PHP 8.4 built-in web server (`php -S localhost:8081`), MySQL, Chromium.
- Playwright, a browser automation tool, ran the tests and took the screenshots (`playwright/tests/carservice.spec.ts`). The database was loaded fresh from `database.sql` before the run.
- Tests 5, 6 and 9 save orders #9–#11. Tests 12 and 15 check those orders, so run the tests in order.
- Test 7 switches off the browser's date check with a script, so the server's own check gets tested too. The app code was not changed.
- Tests 10, 11 and 13 are marked as known failures in Playwright, because the app does not do what the test expects.

## 5. Results table

| # | Purpose | Description | Type of validation | Test data | Expected result | Actual result / Evidence |
|---:|---|---|---|---|---|---|
| 1 | Check that the home page shows the correct statistics. | Before: load a fresh copy of the database.<br>1. Open `/index.php`.<br>2. Read the three statistic cards. | Normal data; black box | Sample data: 8 services, 4 mechanics, 4 completed orders | The cards show 8 Services Available, 4 Expert Mechanics and 4+ Jobs Completed. | The cards showed 8 Services Available, 4 Expert Mechanics and 4+ Jobs Completed. **PASS.** Evidence: `evidence/TC01_home_stats.png` |
| 2 | Check that the services page sorts by price and calculates price statistics. | 1. Open `/services.php`.<br>2. Choose **Price (Low–High)** in Sort by.<br>3. Read the order of the cards and the four statistic boxes. | Normal data; black box | Sort option `price`; sample prices from $18 to $45 (8 services, $235 in total) | Cards run from $18.00 to $45.00. Statistics: cheapest $18.00, most expensive $45.00, average $29.38, 8 services. | The address changed to `services.php?sort=price`. The cards went from Air Filter Replacement ($18.00) to Spark Plug Replacement ($45.00) from cheapest to most expensive. The boxes showed $18.00, $45.00, $29.38 and 8. **PASS.** Evidence: `evidence/TC02_services_by_price.png` |
| 3 | Check that an unknown sort value does not break the services page. | 1. Type `/services.php?sort=abc` in the address bar.<br>2. Read the order of the cards. | Invalid data; black box | Sort value `abc` (not one of `name`, `price`, `duration`) | The page loads with no error and falls back to name order (A–Z). | The page loaded with no error. The cards were in A–Z order, from Air Filter Replacement to Tire Rotation. **PASS.** Evidence: `evidence/TC03_sort_invalid.png` |
| 4 | Check the live price estimate on the booking form. | 1. Open `/order.php`.<br>2. Select **Oil Change ($35.00)** in Select Service.<br>3. Read the Estimated total box. | Normal data; black box | Service Oil Change, price `$35.00`; expected $35.00 + $5.00 call-out + 12% VAT ($4.20) = `$44.20` | The Estimated total box appears and shows $44.20. | The Estimated total box appeared with $44.20 and the note “Includes $5.00 call-out fee + 12% VAT”. **PASS.** Evidence: `evidence/TC04_live_estimate.png` |
| 5 | Check that a customer can book a service for a future date. | 1. Open `/order.php`.<br>2. Enter name `Aliya Normal`, select Oil Change and mechanic Arman Bekov.<br>3. Set Preferred Date to tomorrow (`2026-10-09`).<br>4. Click **Confirm Booking**. | Normal data; black box | Name `Aliya Normal`; service Oil Change ($35.00); mechanic Arman Bekov; date `2026-10-09` (tomorrow) | The app shows “Your booking is confirmed! Order #9” and an Order Summary with total $44.20. | The alert said “Your booking is confirmed! Order #9”. The Order Summary listed Oil Change $35.00, call-out fee $5.00, VAT $4.20, duration 30 min and Total $44.20. **PASS.** Evidence: `evidence/TC05_booking_tomorrow.png` |
| 6 | Check that the earliest allowed date (today) is accepted with the same-day surcharge. | 1. Open `/order.php`.<br>2. Enter name `Berik Borderline`, select Oil Change and Arman Bekov.<br>3. Set Preferred Date to today (`2026-10-08`).<br>4. Click **Confirm Booking**. | Borderline data; black box | Date `2026-10-08` (today, the first date the form accepts); service Oil Change ($35.00); expected surcharge 20% = $7.00 | The app accepts the booking and adds “Same-day surcharge (20%) +$7.00”, so the total is $51.20. | The alert said “Your booking is confirmed! Order #10”. The summary showed Same-day surcharge (20%) +$7.00 and Total $51.20. **PASS.** Evidence: `evidence/TC06_booking_today.png` |
| 7 | Check that the app rejects a booking date in the past. | 1. Open `/order.php`.<br>2. Enter name `Ivan Invalid`, select Oil Change and Arman Bekov.<br>3. Set Preferred Date to yesterday (`2026-10-07`) and click **Confirm Booking**. The browser stops the form first.<br>4. Switch off the browser check (the test does this with a script; the app code stays the same) and send the form again, so the server check is tested too. | Invalid data; black box | Date `2026-10-07` (one day before the earliest allowed date) | The app rejects the booking with “Please select a future date.” and saves no order. | The browser showed the warning “Value must be 08.10.2026 or later.” (`evidence/TC07_client_validation.png`). With the browser check off, the server showed “Please select a future date.” and no Order Summary. No order was saved. **PASS.** Evidence: `evidence/TC07_past_date_rejected.png`, `evidence/TC07_client_validation.png` |
| 8 | Check that a name made only of spaces is rejected. | 1. Open `/order.php`.<br>2. Type five spaces in Your Full Name.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Invalid data; black box | Name made of 5 spaces (the browser accepts it because the field is not empty; the server removes the spaces and finds nothing left) | The app rejects the booking with “Please enter your name.” | The page showed “Please enter your name.” and no Order Summary. No order was saved. **PASS.** Evidence: `evidence/TC08_blank_name.png` |
| 9 | Check that the longest name the database allows is accepted. | 1. Open `/order.php`.<br>2. Enter a name of 100 letters `A`.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Borderline data; black box | Name `AAAA…A` (100 characters, the most the database can store) | The app accepts the booking and shows a confirmation with total $44.20. | The alert said “Your booking is confirmed! Order #11” with Total $44.20. The Orders page lists the full 100-character name. **PASS.** Evidence: `evidence/TC09_name_100.png` |
| 10 | Check that a name longer than the database allows is rejected with a message. | 1. Open `/order.php`.<br>2. Enter a name of 101 letters `B`.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Invalid data; black box | Name `BBBB…B` (101 characters, one more than the database can store) | The app rejects the booking and shows an error message on the booking page. No order is saved. | The page went blank, with no message for the user. The server log said “Data too long for column 'customer_name'”. No order was saved: the Orders page in Test 12 still has 11 orders. **FAIL.** Defect: `order.php` never checks the name length. The database refuses the long name, and the page crashes instead of showing an error. Evidence: `evidence/TC10_name_101.png` |
| 11 | Check that a normal name with an apostrophe can be booked. | 1. Open `/order.php`.<br>2. Enter name `Dana O'Connor`.<br>3. Select Oil Change, Arman Bekov and tomorrow's date.<br>4. Click **Confirm Booking**. | Normal data; black box | Name `Dana O'Connor` (a real surname with an apostrophe) | The app accepts the booking and shows “Your booking is confirmed!” with total $44.20. | The page went blank, with no message for the user. The server log said “You have an error in your SQL syntax … near 'Connor'”. No order was saved. **FAIL.** Defect: `order.php` (line 81) pastes the name straight into the database command. The apostrophe ends the name too early and breaks the command. An attacker could use the same gap to run their own database commands (SQL injection). Evidence: `evidence/TC11_name_apostrophe.png` |
| 12 | Check that the orders page joins the three tables and totals the prices. | Before: run Tests 5, 6 and 9 (they save orders #9–#11).<br>1. Open `/orders.php`.<br>2. Read the totals and the table. | Normal data; black box | 8 seed orders ($235.00) + 3 new Oil Change orders (3 × $35.00) | The page shows 11 orders, total revenue $340.00 and average $30.91. Each row shows the customer, service, mechanic, date, price and status. | The page showed 11 Total orders, $340.00 Total revenue and $30.91 Average order value. The table listed all 11 orders with the service and mechanic names, newest date first. **PASS.** Note: the 100-character name from Test 9 stretches the table past the page width. Evidence: `evidence/TC12_orders_list.png` |
| 13 | Check that the binary search finds the lowest order number. | 1. Open `/orders.php`.<br>2. Enter `1` in Binary search by Order #.<br>3. Click **Search**. | Borderline data; black box | Order # `1` (the lowest order number that exists and the lowest the field accepts) | The app shows a card “Order #1 found” with Marat Usenov, Oil Change, Arman Bekov. | The page showed “Order #1 not found.”, yet order 1 (Marat Usenov) is in the table below it. **FAIL.** Defect: `orders.php` (line 63) compares the order number from the database, stored as text, with the number typed in, using a strict check (`===`). The strict check never matches text with a number, so the search never finds any order. Evidence: `evidence/TC13_search_order1.png` |
| 14 | Check that searching for an order that does not exist shows a message. | 1. Open `/orders.php`.<br>2. Enter `999` in Binary search by Order #.<br>3. Click **Search**. | Invalid data; black box | Order # `999` (no such order) | The app shows “Order #999 not found.” and keeps the order table visible. | The page showed “Order #999 not found.” above the full order table. **PASS.** Evidence: `evidence/TC14_search_order999.png` |
| 15 | Check that the mechanics page counts orders and earnings per mechanic. | Before: run Tests 5, 6 and 9 (they add three Oil Change orders for Arman Bekov).<br>1. Open `/mechanics.php`.<br>2. Read the team rating and each mechanic card. | Normal data; black box | Ratings 4.9, 4.8, 4.7, 4.6; Arman Bekov: 3 seed orders ($105.00) + 3 new ($105.00) | Team average rating 4.8 and 4 mechanics. Arman Bekov shows 6 orders and $210.00 earned. | The page showed “Team average rating: ★ 4.8” and “4 mechanics in total”. Cards: Arman Bekov 6 orders, $210.00; Ruslan Akhmetov 2, $47.00; Dana Seitkali 2, $63.00; Asel Nurlanovna 1, $20.00. **PASS.** Evidence: `evidence/TC15_mechanics.png` |

## 6. Defects found

| Test | Severity | Problem | Where | Suggested fix |
|---:|---|---|---|---|
| 11 | High | A name with an apostrophe, such as `O'Connor`, crashes the booking page. The app pastes the name straight into the database command, so an attacker could also run their own commands (SQL injection). | `order.php`, lines 81–84 | Use a prepared statement for the insert. |
| 13 | High | The order search never finds an order. The order number from the database is text, and the strict check (`===`) compares it with a number. | `orders.php`, line 63 | Convert the order number with `(int)` before comparing. |
| 10 | Medium | A name longer than 100 characters crashes the booking page instead of showing a message. | `order.php`, lines 38–49 and 84 | Check the length before saving and add `maxlength="100"` to the name field. |
| – | Medium | When saving fails, the app shows the raw database error to the user (“Error saving order: …”). This tells an attacker about the table and its columns. In this setup PHP crashes first (Tests 10 and 11), but the code still contains the leak. | `order.php`, line 101 | Show a general message and write the details to the server log. |
| 12 | Low | A very long name stretches the orders and mechanics tables wider than the page. | `css/style.css`, table cells | Let long words wrap, for example with `overflow-wrap: anywhere`. |

## 7. Completion checklist

- [x] MySQL and the web server were running.
- [x] The database was re-imported from `database.sql` before the run.
- [x] All 15 tests were executed in order.
- [x] Normal, borderline and invalid test data were included (7 normal, 3 borderline, 5 invalid).
- [x] Every Actual Result is based on observation.
- [x] Every test has PASS or FAIL.
- [x] Every test has at least one clear screenshot.
- [x] Every failed test has a defect note (section 6).
- [x] The defects table also lists one problem found by reading the code (raw database error shown to the user).
