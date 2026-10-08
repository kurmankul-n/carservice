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

The testing documentation contains 15 black-box tests using normal, borderline and invalid data. Result: 12 PASS and 3 FAIL.

| File | Contents |
|---|---|
| `Testing_Documentation.md` | Test plan, results table, defects, checklist |
| `Testing_Results_Table.docx` | Results table with screenshots for submission |
| `evidence/` | Screenshots of every test |
| `playwright/tests/carservice.spec.ts` | Automated tests that produce the screenshots |

Run the tests on a fresh database:

```bash
npm install
npx playwright install chromium      # first time only
mysql -uroot -e "DROP DATABASE IF EXISTS car_service_db" && mysql -uroot < database.sql
php -S localhost:8081 &              # keep the server running
npx playwright test
```

Tests 10, 11 and 13 are marked `test.fail` because they expose defects in the app:
- a name with an apostrophe crashes the booking page (SQL injection risk);
- a name over 100 characters crashes the booking page;
- the binary search never finds an order.

See section 6 of `Testing_Documentation.md`.
