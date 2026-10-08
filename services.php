<?php
// ============================================================
// services.php  — List all services
// Demonstrates: SQL from 1 table, array, bubble sort, loop
// ============================================================

require_once 'includes/db.php';

// ---- SQL: Select all services from ONE table ----
$query  = "SELECT service_id, service_name, description, price, duration_min FROM services";
$result = mysqli_query($connection, $query);

// ---- Store results in a one-dimensional array ----
$services_array = [];                       // initialise empty array
while ($row = mysqli_fetch_assoc($result)) {
    $services_array[] = $row;               // append each row to the array
}

// ---- Get sort preference from the URL (conditional structure) ----
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';

// ---- Bubble Sort algorithm on the $services_array ----
// Sorts the array in-place depending on the chosen column
$n = count($services_array);               // array length

for ($i = 0; $i < $n - 1; $i++) {         // outer loop (passes)
    for ($j = 0; $j < $n - $i - 1; $j++) { // inner loop (comparisons)

        // Determine which value to compare (conditional structure)
        if ($sort_by === 'price') {
            $a = $services_array[$j]['price'];
            $b = $services_array[$j + 1]['price'];
        } elseif ($sort_by === 'duration') {
            $a = $services_array[$j]['duration_min'];
            $b = $services_array[$j + 1]['duration_min'];
        } else {
            // Default: sort by name (alphabetical)
            $a = $services_array[$j]['service_name'];
            $b = $services_array[$j + 1]['service_name'];
        }

        // Swap if out of order
        if ($a > $b) {
            $temp                    = $services_array[$j];
            $services_array[$j]      = $services_array[$j + 1];
            $services_array[$j + 1]  = $temp;
        }
    }
}

// ---- Simple math: calculate cheapest, most expensive, and average price ----
$total_price   = 0;
$price_values  = [];                        // one-dimensional array of prices

foreach ($services_array as $service) {     // loop through array
    $total_price    += $service['price'];   // accumulate sum
    $price_values[]  = $service['price'];   // collect prices
}

$avg_price = $total_price / count($price_values);   // average price calculation
$min_price = min($price_values);                    // cheapest service
$max_price = max($price_values);                    // most expensive service
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Services — AutoCall</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Internal CSS — page-specific */
        .stats-mini { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
        .stat-mini  { background:#fff; border-radius:8px; padding:12px 20px;
                      box-shadow:0 2px 8px rgba(0,0,0,.06); font-size:13px;
                      color:var(--gray-dark); }
        .stat-mini strong { display:block; font-family:var(--font-head);
                            font-size:20px; color:var(--accent); }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">Auto<span>Call</span></a>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="services.php" class="active">Services</a></li>
        <li><a href="order.php">Book Service</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="mechanics.php">Mechanics</a></li>
    </ul>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Our Services</h1>
        <p>All services performed on-site at your location.</p>
    </div>

    <!-- Mini stats (mathematical calculations) -->
    <div class="stats-mini">
        <div class="stat-mini">
            <strong>$<?php echo number_format($min_price, 2); ?></strong>
            Cheapest service
        </div>
        <div class="stat-mini">
            <strong>$<?php echo number_format($max_price, 2); ?></strong>
            Most expensive
        </div>
        <div class="stat-mini">
            <!-- Inline CSS on a specific element -->
            <strong style="color:var(--black);">$<?php echo number_format($avg_price, 2); ?></strong>
            Average price
        </div>
        <div class="stat-mini">
            <strong><?php echo count($services_array); ?></strong>
            Total services
        </div>
    </div>

    <!-- Sort controls -->
    <div class="sort-bar">
        <label>Sort by:</label>
        <select onchange="window.location='services.php?sort='+this.value">
            <option value="name"     <?php if ($sort_by==='name')     echo 'selected'; ?>>Name (A–Z)</option>
            <option value="price"    <?php if ($sort_by==='price')    echo 'selected'; ?>>Price (Low–High)</option>
            <option value="duration" <?php if ($sort_by==='duration') echo 'selected'; ?>>Duration (Fastest)</option>
        </select>
        <span style="font-size:12px;color:var(--gray-dark);">
            Sorted using Bubble Sort algorithm
        </span>
    </div>

    <!-- Services grid — loop through array to render cards -->
    <div class="cards-grid">
        <?php foreach ($services_array as $service): ?>
            <div class="card">
                <div class="card-title"><?php echo htmlspecialchars($service['service_name']); ?></div>
                <div class="card-desc"><?php echo htmlspecialchars($service['description']); ?></div>
                <div class="card-footer">
                    <span class="price-tag">$<?php echo number_format($service['price'], 2); ?></span>
                    <span class="duration-tag"><?php echo $service['duration_min']; ?> min</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> AutoCall — Mobile Car Service Application</p>
</footer>

</body>
</html>
