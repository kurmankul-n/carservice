<?php
// ============================================================
// index.php  — Homepage
// Mobile Car Service Application
// ============================================================

// Include database connection (scripting language connects to DB)
require_once 'includes/db.php';

// ---- Count statistics using SQL (SELECT from single tables) ----

// Count total services available
$result_services = mysqli_query($connection, "SELECT COUNT(*) AS total FROM services");
$row_services    = mysqli_fetch_assoc($result_services);
$total_services  = $row_services['total'];

// Count total mechanics
$result_mechanics = mysqli_query($connection, "SELECT COUNT(*) AS total FROM mechanics");
$row_mechanics    = mysqli_fetch_assoc($result_mechanics);
$total_mechanics  = $row_mechanics['total'];

// Count completed orders
$result_orders = mysqli_query($connection, "SELECT COUNT(*) AS total FROM orders WHERE status = 'Completed'");
$row_orders    = mysqli_fetch_assoc($result_orders);
$total_completed = $row_orders['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AutoCall — On-Site Car Service</title>
    <!-- Link to external CSS stylesheet (CSS Type 1: External) -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Internal CSS (CSS Type 2: Internal) — page-specific overrides -->
    <style>
        .hero { min-height: 420px; display: flex; flex-direction: column; justify-content: center; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 40px; }
        .feature-item { text-align: center; padding: 20px; }
        .feature-icon { font-size: 32px; margin-bottom: 12px; }
        .feature-item h3 { font-family: var(--font-head); font-size: 16px; margin-bottom: 6px; }
        .feature-item p  { font-size: 13px; color: var(--gray-dark); }
    </style>
</head>
<body>

<!-- Navigation -->
<nav>
    <a href="index.php" class="nav-logo">Auto<span>Call</span></a>
    <ul>
        <li><a href="index.php" class="active">Home</a></li>
        <li><a href="services.php">Services</a></li>
        <li><a href="order.php">Book Service</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="mechanics.php">Mechanics</a></li>
    </ul>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div style="max-width:600px; margin:0 auto; text-align:center; padding:40px;">
        <h1>On-Site Car<br><span>Repair & Service</span></h1>
        <p>A mechanic comes to you — anywhere in the city. Book in minutes, track in real time.</p>
        <div class="hero-buttons">
            <a href="services.php" class="btn btn-primary">View Services</a>
            <a href="order.php" class="btn btn-outline">Book Now</a>
        </div>
    </div>
</section>

<!-- Stats Row -->
<div class="container">
    <div class="stats-row">
        <div class="stat-card">
            <!-- Inline CSS (CSS Type 3: Inline) -->
            <span class="stat-number" style="font-size:42px;"><?php echo $total_services; ?></span>
            <span class="stat-label">Services Available</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_mechanics; ?></span>
            <span class="stat-label">Expert Mechanics</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo $total_completed; ?>+</span>
            <span class="stat-label">Jobs Completed</span>
        </div>
    </div>

    <!-- Features section -->
    <div class="features-grid">
        <div class="feature-item">
            <div class="feature-icon">🔧</div>
            <h3>Fast Booking</h3>
            <p>Select your service, confirm location, and a mechanic is dispatched within minutes.</p>
        </div>
        <div class="feature-item">
            <div class="feature-icon">📍</div>
            <h3>We Come to You</h3>
            <p>No need to visit a garage. Our mechanics travel to your home, office, or roadside.</p>
        </div>
        <div class="feature-item">
            <div class="feature-icon">⭐</div>
            <h3>Rated Mechanics</h3>
            <p>All mechanics are verified and rated by customers for quality and reliability.</p>
        </div>
    </div>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> AutoCall — Mobile Car Service Application</p>
</footer>

</body>
</html>
