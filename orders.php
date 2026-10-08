<?php
// ============================================================
// orders.php  — View all orders
// Demonstrates: SQL JOIN (multiple tables), binary search, loop
// ============================================================

require_once 'includes/db.php';

// ---- SQL: SELECT from MULTIPLE TABLES using JOIN ----
// Joins orders + services + mechanics to get full order info
$query = "
    SELECT
        o.order_id,
        o.customer_name,
        o.order_date,
        o.status,
        s.service_name,
        s.price,
        m.full_name    AS mechanic_name,
        m.specialization
    FROM orders        AS o
    JOIN services      AS s ON o.service_id  = s.service_id
    JOIN mechanics     AS m ON o.mechanic_id = m.mechanic_id
    ORDER BY o.order_date DESC
";

$result = mysqli_query($connection, $query);

// ---- Store all orders in a one-dimensional array ----
$orders_array = [];
while ($row = mysqli_fetch_assoc($result)) {
    $orders_array[] = $row;
}

// ---- Binary Search: find a specific order by ID ----
// Binary search requires a sorted array; sort by order_id first
$search_id     = isset($_GET['search']) ? (int) $_GET['search'] : 0;
$found_order   = null;

if ($search_id > 0) {
    // Sort array by order_id ascending (required for binary search)
    $sorted = $orders_array;
    $n = count($sorted);

    // Simple insertion sort to sort by order_id before binary search
    for ($i = 1; $i < $n; $i++) {
        $key = $sorted[$i];
        $j   = $i - 1;
        while ($j >= 0 && $sorted[$j]['order_id'] > $key['order_id']) {
            $sorted[$j + 1] = $sorted[$j];
            $j--;
        }
        $sorted[$j + 1] = $key;
    }

    // Binary search for the order_id
    $low  = 0;
    $high = $n - 1;

    while ($low <= $high) {
        $mid = (int)(($low + $high) / 2);   // find middle index

        if ($sorted[$mid]['order_id'] === $search_id) {
            $found_order = $sorted[$mid];    // found!
            break;
        } elseif ($sorted[$mid]['order_id'] < $search_id) {
            $low = $mid + 1;                 // search right half
        } else {
            $high = $mid - 1;                // search left half
        }
    }
}

// ---- Math: calculate totals ----
$total_revenue = 0;
foreach ($orders_array as $order) {
    $total_revenue += $order['price'];
}
$average_order = count($orders_array) > 0 ? $total_revenue / count($orders_array) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders — AutoCall</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Internal CSS — orders page specific */
        .search-bar { display:flex; gap:12px; margin-bottom:28px; align-items:center; }
        .search-bar input { padding:10px 14px; border:1.5px solid var(--gray-mid);
                            border-radius:8px; font-size:14px; width:180px; outline:none; }
        .search-bar input:focus { border-color:var(--accent); }
        .found-card { background:var(--black); color:var(--white); border-radius:var(--radius);
                      padding:20px 24px; margin-bottom:24px; }
        .found-card h3 { font-family:var(--font-head); color:var(--accent); margin-bottom:8px; }
        .totals-row { display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
        .total-pill { background:#fff; border-radius:8px; padding:10px 18px;
                      font-size:13px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
        .total-pill strong { font-family:var(--font-head); color:var(--accent);
                              font-size:18px; display:block; }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">Auto<span>Call</span></a>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="order.php">Book Service</a></li>
        <li><a href="orders.php" class="active">Orders</a></li>
        <li><a href="mechanics.php">Mechanics</a></li>
    </ul>
</nav>

<div class="container">
    <div class="page-header">
        <h1>All Orders</h1>
        <p>Overview of all service bookings. Uses SQL JOIN across 3 tables.</p>
    </div>

    <!-- Totals (mathematical calculations) -->
    <div class="totals-row">
        <div class="total-pill">
            <strong><?php echo count($orders_array); ?></strong>
            Total orders
        </div>
        <div class="total-pill">
            <!-- Inline CSS on specific element -->
            <strong style="color:var(--black);">$<?php echo number_format($total_revenue, 2); ?></strong>
            Total revenue
        </div>
        <div class="total-pill">
            <strong>$<?php echo number_format($average_order, 2); ?></strong>
            Average order value
        </div>
    </div>

    <!-- Binary search form -->
    <div class="search-bar">
        <form method="GET" action="orders.php" style="display:flex;gap:10px;align-items:center;">
            <label style="font-size:13px;color:var(--gray-dark);font-weight:500;">
                Binary search by Order #:
            </label>
            <input type="number" name="search" placeholder="e.g. 3"
                   value="<?php echo $search_id ?: ''; ?>" min="1">
            <button type="submit" class="btn btn-primary" style="padding:10px 18px;font-size:14px;">
                Search
            </button>
            <?php if ($search_id > 0): ?>
                <a href="orders.php" style="font-size:13px;color:var(--gray-dark);">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Show binary search result (conditional) -->
    <?php if ($search_id > 0): ?>
        <?php if ($found_order): ?>
            <div class="found-card">
                <h3>Order #<?php echo $found_order['order_id']; ?> found</h3>
                <p>
                    <strong><?php echo htmlspecialchars($found_order['customer_name']); ?></strong>
                    — <?php echo htmlspecialchars($found_order['service_name']); ?>
                    | Mechanic: <?php echo htmlspecialchars($found_order['mechanic_name']); ?>
                    | Date: <?php echo $found_order['order_date']; ?>
                    | $<?php echo number_format($found_order['price'], 2); ?>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-error">Order #<?php echo $search_id; ?> not found.</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Orders table — loop through the $orders_array -->
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Mechanic</th>
                <th>Date</th>
                <th>Price</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders_array as $order): ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['mechanic_name']); ?></td>
                    <td><?php echo $order['order_date']; ?></td>
                    <td>$<?php echo number_format($order['price'], 2); ?></td>
                    <td>
                        <?php
                        // Conditional structure: assign badge class based on status
                        if ($order['status'] === 'Completed') {
                            $badge_class = 'badge-completed';
                        } elseif ($order['status'] === 'In Progress') {
                            $badge_class = 'badge-progress';
                        } else {
                            $badge_class = 'badge-pending';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> AutoCall — Mobile Car Service Application</p>
</footer>

</body>
</html>
