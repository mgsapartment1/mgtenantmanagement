<?php
include('db.php');
session_start();

// FETCH UNITS: Kunin lahat ng units na 'Available' ang status
// Inayos natin ang query para sigurado ang pagkaka-sunod sunod
$query = "SELECT * FROM units WHERE status = 'Available' ORDER BY unit_no ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Units - EJ's Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: #008080; } 
        .unit-card { 
            border: none; 
            border-radius: 20px; 
            transition: all 0.3s ease;
            background: white;
            overflow: hidden; /* Para hindi lumampas ang image sa rounded corners */
        }
        .unit-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; 
        }
        /* Style para sa image para laging sakto ang sukat */
        .unit-img-container {
            height: 200px;
            width: 100%;
            overflow: hidden;
        }
        .unit-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .price-tag { 
            font-size: 1.6rem; 
            font-weight: 800; 
            color: #008080; 
        }
        .btn-teal { background: #008080; color: white; border: none; border-radius: 10px; }
        .btn-teal:hover { background: #006666; color: white; }
        .badge-available { background: #e0f2f1; color: #008080; font-weight: 600; }
        .amenity-icon { color: #008080; font-size: 0.9rem; margin-right: 10px; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">
            <img src="logo.png" width="35" class="me-2"> EJ'S RENTAL
        </a>
        <div class="ms-auto">
            <?php if(isset($_SESSION['user_id'])): ?>
                <span class="text-white me-3 d-none d-md-inline small">Hi, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="tenant_dashboard.php" class="btn btn-light btn-sm fw-bold rounded-pill px-3">My Dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light btn-sm rounded-pill px-3 me-2">Login</a>
                <a href="register.php" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold display-6">Find Your Next Home</h1>
        <p class="text-muted">High-quality units at an affordable price. Reserve yours now!</p>
        <hr class="mx-auto" style="width: 50px; height: 3px; background: #008080; opacity: 1;">
    </div>

    <div class="row g-4">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4">
                    <div class="card h-100 unit-card shadow-sm">
                        <div class="unit-img-container">
                            <img src="uploads/units/<?php echo htmlspecialchars($row['image_name'] ?: 'default.png'); ?>" 
                                 alt="Unit <?php echo htmlspecialchars($row['unit_no']); ?>"
                                 onerror="this.src='uploads/units/default.png';">
                        </div>
                        
                        <div class="card-body px-4 pt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold mb-0">UNIT <?php echo htmlspecialchars($row['unit_no']); ?></h4>
                                <div class="price-tag">₱<?php echo number_format($row['monthly_rent'], 2); ?><small class="text-muted fw-normal" style="font-size: 0.8rem;">/mo</small></div>
                            </div>

                            <div class="mb-3">
                                <span class="badge badge-available px-3 py-2 rounded-pill"><?php echo htmlspecialchars($row['unit_type'] ?? 'Studio'); ?></span>
                                <span class="ms-2 text-muted small"><i class="fa fa-ruler-combined"></i> <?php echo htmlspecialchars($row['floor_area'] ?? '18'); ?>sqm</span>
                            </div>
                            
                            <p class="text-muted small mb-4" style="min-height: 40px;">
                                <?php echo htmlspecialchars($row['description'] ?? 'Cozy living space with modern amenities and 24/7 security.'); ?>
                            </p>

                            <div class="mb-4">
                                <div class="small mb-2"><i class="fa fa-bolt amenity-icon"></i>Submetered Electricity</div>
                                <div class="small mb-2"><i class="fa fa-droplet amenity-icon"></i>Shared Water Source</div>
                                <div class="small mb-2"><i class="fa fa-shield-alt amenity-icon"></i>Secure Premises</div>
                            </div>

                            <?php 
                                // Logic for redirection
                                if(isset($_SESSION['user_id'])) {
                                    $target_link = "tenant_dashboard.php?request_unit=" . $row['id'];
                                } else {
                                    $target_link = "login.php?unit_id=" . $row['id'];
                                }
                            ?>
                            
                            <a href="<?php echo $target_link; ?>" class="btn btn-teal w-100 fw-bold py-3 shadow-sm">
                                <i class="fa fa-key me-2"></i> RENT THIS UNIT
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fa fa-calendar-times fa-4x text-muted mb-3 opacity-50"></i>
                <h4 class="text-muted fw-bold">All units are currently occupied.</h4>
                <p class="text-muted">Please check back later for new openings.</p>
                <a href="index.php" class="btn btn-outline-dark rounded-pill mt-3 px-4">Back to Home</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer class="text-center py-4 text-muted mt-5 border-top">
    <small>&copy; <?php echo date('Y'); ?> MG's Tenant Management System</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>