<?php
// ============================================================
// mechanics.php  — View mechanics and their completed orders
// Demonstrates: SQL JOIN (multiple tables), loop, conditional
// ============================================================

require_once 'includes/db.php';

// ---- SQL: SELECT from MULTIPLE TABLES (mechanics + orders + services) ----
// Get each mechanic with count of their orders and total earnings
$query = "
    SELECT
        m.mechanic_id,
        m.full_name,
        m.specialization,
        m.rating,
        COUNT(o.order_id)   AS total_orders,
        SUM(s.price)        AS total_earned
    FROM mechanics AS m
    LEFT JOIN orders   AS o ON m.mechanic_id = o.mechanic_id
    LEFT JOIN services AS s ON o.service_id  = s.service_id
    GROUP BY m.mechanic_id, m.full_name, m.specialization, m.rating
    ORDER BY m.rating DESC
";

$result         = mysqli_query($connection, $query);
$mechanics_data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $mechanics_data[] = $row;
}

// ---- Math: calculate average rating ----
$rating_sum = 0;
foreach ($mechanics_data as $mech) {
    $rating_sum += $mech['rating'];
}
$average_rating = count($mechanics_data) > 0 ? $rating_sum / count($mechanics_data) : 0;

// ---- SQL: Get recent orders per mechanic (JOIN across 2 tables) ----
$recent_query = "
    SELECT
        o.order_id,
        o.customer_name,
        o.order_date,
        o.status,
        o.mechanic_id,
        s.service_name,
        s.price
    FROM orders   AS o
    JOIN services AS s ON o.service_id = s.service_id
    ORDER BY o.order_date DESC
    LIMIT 10
";

$recent_result  = mysqli_query($connection, $recent_query);
$recent_orders  = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent_orders[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mechanics — AutoCall</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Internal CSS — mechanics page layout */
        .mechanics-list { display:grid; gap:16px; margin-bottom:40px; }
        .mech-stats { display:flex; gap:20px; margin-top:8px; font-size:13px; }
        .mech-stat  { color:var(--gray-dark); }
        .mech-stat strong { color:var(--black); }
        .star { color:var(--accent); }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">Auto<span>Call</span></a>
    <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="order.php">Book Service</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="mechanics.php" class="active">Mechanics</a></li>
    </ul>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Our Mechanics</h1>
        <p>Team overview with order counts. Uses SQL JOIN across 3 tables.</p>
    </div>

    <!-- Average rating stat (inline CSS on specific element) -->
    <p style="font-size:14px;color:var(--gray-dark);margin-bottom:24px;">
        Team average rating:
        <strong style="font-family:var(--font-head);font-size:18px;color:var(--accent);">
            &#9733; <?php echo number_format($average_rating, 1); ?>
        </strong>
        &nbsp;|&nbsp;
        <?php echo count($mechanics_data); ?> mechanics in total
    </p>

    <!-- Mechanics cards — loop through array -->
    <div class="mechanics-list">
        <?php foreach ($mechanics_data as $mech): ?>
            <div class="mechanic-card">
                <!-- Avatar: first letters of name -->
                <div class="mechanic-avatar">
                    <?php
                    // Get initials using array/string operations
                    $name_parts = explode(' ', $mech['full_name']);
                    $initials   = '';
                    foreach ($name_parts as $part) {
                        $initials .= strtoupper($part[0]);
                    }
                    echo substr($initials, 0, 2);
                    ?>
                </div>
                <div class="mechanic-info" style="flex:1;">
                    <h3><?php echo htmlspecialchars($mech['full_name']); ?></h3>
                    <div class="mechanic-spec"><?php echo htmlspecialchars($mech['specialization']); ?></div>
                    <div class="mech-stats">
                        <span class="mech-stat">
                            <strong><?php echo $mech['total_orders']; ?></strong> orders
                        </span>
                        <span class="mech-stat">
                            <strong>
                                $<?php echo $mech['total_earned'] ? number_format($mech['total_earned'], 2) : '0.00'; ?>
                            </strong> earned
                        </span>
                    </div>
                </div>
                <!-- Rating badge (conditional: colour changes by rating) -->
                <div style="text-align:right;">
                    <?php
                    // Conditional: choose star colour by rating value
                    if ($mech['rating'] >= 4.8) {
                        $rating_color = 'var(--accent)';
                    } elseif ($mech['rating'] >= 4.5) {
                        $rating_color = '#f0a500';
                    } else {
                        $rating_color = 'var(--gray-dark)';
                    }
                    ?>
                    <span style="font-family:var(--font-head);font-size:22px;
                                 color:<?php echo $rating_color; ?>;">
                        &#9733; <?php echo $mech['rating']; ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent orders table (JOIN: orders + services) -->
    <div class="page-header">
        <h1 style="font-size:24px;">Recent Orders</h1>
        <p>Latest 10 bookings — joined from orders &amp; services tables.</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Price</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <!-- Loop through recent_orders array -->
            <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['service_name']); ?></td>
                    <td>$<?php echo number_format($order['price'], 2); ?></td>
                    <td><?php echo $order['order_date']; ?></td>
                    <td>
                        <?php
                        // Conditional for status badge
                        if ($order['status'] === 'Completed') {
                            echo '<span class="badge badge-completed">Completed</span>';
                        } elseif ($order['status'] === 'In Progress') {
                            echo '<span class="badge badge-progress">In Progress</span>';
                        } else {
                            echo '<span class="badge badge-pending">Pending</span>';
                        }
                        ?>
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
