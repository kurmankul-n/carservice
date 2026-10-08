<?php
// ============================================================
// order.php  — Book a service
// Demonstrates: form handling, math calculation, conditionals,
//               SQL INSERT, SQL SELECT from one table
// ============================================================

require_once 'includes/db.php';

// ---- Fetch services for dropdown (SQL: SELECT from one table) ----
$services_result = mysqli_query($connection, "SELECT service_id, service_name, price FROM services ORDER BY service_name");
$services_list   = [];
while ($row = mysqli_fetch_assoc($services_result)) {
    $services_list[] = $row;                // build array of services
}

// ---- Fetch mechanics for dropdown (SQL: SELECT from one table) ----
$mechanics_result = mysqli_query($connection, "SELECT mechanic_id, full_name, specialization FROM mechanics ORDER BY full_name");
$mechanics_list   = [];
while ($row = mysqli_fetch_assoc($mechanics_result)) {
    $mechanics_list[] = $row;               // build array of mechanics
}

// ---- Handle form submission ----
$message      = '';
$message_type = '';
$order_summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and sanitise input values
    $customer_name = trim($_POST['customer_name']);
    $service_id    = (int) $_POST['service_id'];
    $mechanic_id   = (int) $_POST['mechanic_id'];
    $order_date    = $_POST['order_date'];

    // ---- Input validation (conditional structure) ----
    if (empty($customer_name)) {
        $message      = "Please enter your name.";
        $message_type = "error";
    } elseif ($service_id <= 0) {
        $message      = "Please select a service.";
        $message_type = "error";
    } elseif ($mechanic_id <= 0) {
        $message      = "Please select a mechanic.";
        $message_type = "error";
    } elseif (empty($order_date)) {
        $message      = "Please select a date.";
        $message_type = "error";
    } else {
        // ---- Get service price for calculation ----
        $price_query  = "SELECT service_name, price, duration_min FROM services WHERE service_id = $service_id";
        $price_result = mysqli_query($connection, $price_query);
        $service_data = mysqli_fetch_assoc($price_result);

        // ---- Mathematical calculations ----
        $base_price    = (float) $service_data['price'];
        $call_out_fee  = 5.00;                        // flat call-out fee
        $vat_rate      = 0.12;                        // 12% VAT rate
        $vat_amount    = $base_price * $vat_rate;     // calculate VAT
        $total_price   = $base_price + $call_out_fee + $vat_amount; // total

        // ---- Determine urgency surcharge (conditional structure) ----
        $today           = date('Y-m-d');
        $urgency_charge  = 0;
        $urgency_label   = '';

        if ($order_date === $today) {
            // Same-day booking: 20% surcharge
            $urgency_charge = $base_price * 0.20;
            $urgency_label  = 'Same-day surcharge (20%)';
        } elseif ($order_date < $today) {
            $message      = "Please select a future date.";
            $message_type = "error";
        }

        if ($message_type !== 'error') {
            $total_price += $urgency_charge;

            // ---- Insert order into database ----
            $insert_query = "INSERT INTO orders (customer_name, service_id, mechanic_id, order_date, status)
                             VALUES ('$customer_name', $service_id, $mechanic_id, '$order_date', 'Pending')";

            if (mysqli_query($connection, $insert_query)) {
                $message      = "Your booking is confirmed! Order #" . mysqli_insert_id($connection);
                $message_type = "success";

                // Store summary to display
                $order_summary = [
                    'service'        => $service_data['service_name'],
                    'duration'       => $service_data['duration_min'],
                    'base_price'     => $base_price,
                    'call_out_fee'   => $call_out_fee,
                    'vat_amount'     => $vat_amount,
                    'urgency_charge' => $urgency_charge,
                    'urgency_label'  => $urgency_label,
                    'total_price'    => $total_price,
                    'order_date'     => $order_date,
                ];
            } else {
                $message      = "Error saving order: " . mysqli_error($connection);
                $message_type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book a Service — AutoCall</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Internal CSS — two-column layout on large screens */
        .order-layout { display:grid; grid-template-columns:1fr 1fr; gap:32px; align-items:start; }
        @media(max-width:720px) { .order-layout { grid-template-columns:1fr; } }
        .info-panel h2 { font-family:var(--font-head); font-size:22px; margin-bottom:12px; }
        .info-panel p  { font-size:14px; color:var(--gray-dark); line-height:1.7; }
        .step-list     { list-style:none; margin-top:20px; }
        .step-list li  { display:flex; gap:12px; align-items:flex-start;
                         margin-bottom:14px; font-size:14px; }
        .step-num      { background:var(--accent); color:#fff; width:24px; height:24px;
                         border-radius:50%; display:flex; align-items:center;
                         justify-content:center; font-family:var(--font-head);
                         font-size:12px; flex-shrink:0; }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">Auto<span>Call</span></a>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="order.php" class="active">Book Service</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="mechanics.php">Mechanics</a></li>
    </ul>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Book a Service</h1>
        <p>Fill in the form and a mechanic will come to you.</p>
    </div>

    <!-- Display alert messages (conditional) -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="order-layout">
        <!-- Booking form -->
        <div class="form-card">
            <form method="POST" action="order.php">
                <div class="form-group">
                    <label for="customer_name">Your Full Name</label>
                    <input type="text" id="customer_name" name="customer_name"
                           placeholder="e.g. Arman Bekov" required>
                </div>

                <div class="form-group">
                    <label for="service_id">Select Service</label>
                    <select id="service_id" name="service_id" required
                            onchange="updatePrice(this)">
                        <option value="">— Choose a service —</option>
                        <!-- Loop through services array to populate dropdown -->
                        <?php foreach ($services_list as $svc): ?>
                            <option value="<?php echo $svc['service_id']; ?>"
                                    data-price="<?php echo $svc['price']; ?>">
                                <?php echo htmlspecialchars($svc['service_name']); ?>
                                ($<?php echo number_format($svc['price'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="mechanic_id">Choose Mechanic</label>
                    <select id="mechanic_id" name="mechanic_id" required>
                        <option value="">— Choose a mechanic —</option>
                        <!-- Loop through mechanics array -->
                        <?php foreach ($mechanics_list as $mech): ?>
                            <option value="<?php echo $mech['mechanic_id']; ?>">
                                <?php echo htmlspecialchars($mech['full_name']); ?>
                                (<?php echo htmlspecialchars($mech['specialization']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="order_date">Preferred Date</label>
                    <input type="date" id="order_date" name="order_date"
                           min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <!-- Live price preview (updated by JS) -->
                <div id="price-preview" style="display:none;background:var(--gray-light);
                     border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:14px;">
                    <strong>Estimated total:</strong>
                    <span id="estimated-total" style="color:var(--accent);font-family:var(--font-head);font-size:18px;margin-left:8px;"></span>
                    <span style="font-size:12px;color:var(--gray-dark);display:block;margin-top:4px;">
                        Includes $5.00 call-out fee + 12% VAT
                    </span>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">
                    Confirm Booking
                </button>
            </form>
        </div>

        <!-- Info panel + order summary -->
        <div>
            <?php if ($order_summary): ?>
                <!-- Show calculation breakdown if order was placed -->
                <div class="summary-box">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span><?php echo htmlspecialchars($order_summary['service']); ?></span>
                        <span>$<?php echo number_format($order_summary['base_price'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Call-out fee</span>
                        <span>$<?php echo number_format($order_summary['call_out_fee'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>VAT (12%)</span>
                        <span>$<?php echo number_format($order_summary['vat_amount'], 2); ?></span>
                    </div>
                    <!-- Conditional: only show urgency charge if applicable -->
                    <?php if ($order_summary['urgency_charge'] > 0): ?>
                        <div class="summary-row">
                            <span><?php echo $order_summary['urgency_label']; ?></span>
                            <span style="color:#e85d2f;">+$<?php echo number_format($order_summary['urgency_charge'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Duration</span>
                        <span><?php echo $order_summary['duration']; ?> min</span>
                    </div>
                    <div class="summary-total">
                        Total: $<?php echo number_format($order_summary['total_price'], 2); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="info-panel">
                    <h2>How it works</h2>
                    <p>Book a mechanic in 3 easy steps. We come to your location.</p>
                    <ul class="step-list">
                        <li><span class="step-num">1</span> Choose your service and preferred mechanic</li>
                        <li><span class="step-num">2</span> Select your date — same-day available</li>
                        <li><span class="step-num">3</span> Mechanic arrives and completes the job on-site</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> AutoCall — Mobile Car Service Application</p>
</footer>

<!-- JavaScript for live price estimate -->
<script>
// Inline price estimator — updates as user selects a service
function updatePrice(selectElement) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var price          = parseFloat(selectedOption.getAttribute('data-price'));

    // Conditional: only show if a valid service is selected
    if (!isNaN(price) && price > 0) {
        var callout  = 5.00;
        var vat      = price * 0.12;
        var total    = price + callout + vat;

        document.getElementById('estimated-total').textContent = '$' + total.toFixed(2);
        document.getElementById('price-preview').style.display = 'block';
    } else {
        document.getElementById('price-preview').style.display = 'none';
    }
}
</script>

</body>
</html>
