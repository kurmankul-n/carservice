# AutoCall: Mobile Car Service

AutoCall is a PHP/MySQL web application for booking on-site car repairs. A customer picks a service, a mechanic and a date, and the app calculates the price: service price + $5.00 call-out fee + 12% VAT, plus a 20% surcharge for same-day bookings.

## Pages

| Page | Purpose |
|---|---|
| `index.php` | Home page with service, mechanic and completed-job counts |
| `services.php` | Service list with bubble sort (name, price, duration) and price statistics |
| `order.php` | Booking form with a live price estimate and an order summary |
| `orders.php` | All orders (JOIN of 3 tables), totals and binary search by order number |
| `mechanics.php` | Mechanics with order counts, earnings and average rating |

`includes/db.php` holds the database connection. `css/style.css` holds the shared styles.

## Run locally

Requirements: PHP 8 with mysqli, MySQL (or XAMPP).

```bash
mysql -uroot < database.sql          # creates car_service_db with sample data
php -S localhost:8081                # run from this folder
```

Open http://localhost:8081. The connection settings (root, empty password) are in `includes/db.php`.

With XAMPP, copy the folder into `htdocs`, import `database.sql` in phpMyAdmin and open `http://localhost/carservice/`.

## Testing

The testing documentation contains 10 tests using normal, borderline and invalid data and six types of validation (range, presence, length, lookup, special-character and calculation checks). Two types of testing are explained: black box and white box. Result: 7 PASS and 3 FAIL.

| File | Contents |
|---|---|
| `Testing_Documentation.md` | Test plan, validation and testing types, results table, defects, checklist |
| `Test_Explanation_RU.md` / `.docx` | Study guide in Russian: every test explained |
| `Testing_Results_Table.docx` | Results table with screenshots for submission |
| `evidence/` | Screenshots of every test |
| `playwright/tests/carservice.spec.ts` | Automated tests that produce the screenshots |

Run the tests on a fresh database:

```bash
npm install
npx playwright install chromium      # first time only
mysql -uroot -e "DROP DATABASE IF EXISTS car_service_db" && mysql -uroot < database.sql
php -S localhost:8081 -d display_errors=1 &   # keep the server running (display_errors=1 is the XAMPP default)
npx playwright test
```

Tests 7, 8 and 9 are marked `test.fail` because they expose defects in the app:
- a name with an apostrophe crashes the booking page (SQL injection risk);
- a name over 100 characters crashes the booking page;
- the binary search never finds an order.

A crash also shows the user a raw PHP error with the file path and part of the SQL command, and `order.php` prints the raw database error when saving fails. See section 8 of `Testing_Documentation.md`.
