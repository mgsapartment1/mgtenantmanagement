<?php 
include('db.php'); 
session_start();

// GUMAMIT NG SUBQUERY PARA I-EXCLUDE ANG MGA UNITS NA MAY PENDING/APPROVED REQUESTS
$query = "SELECT * FROM units 
          WHERE status = 'Available' 
          AND id NOT IN (SELECT unit_id FROM rent_requests WHERE status = 'Pending' OR status = 'Approved')
          ORDER BY id ASC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MG APARTMENT - Available Units</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --teal-color: #008080; --light-teal: #e0f2f1; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Roboto, sans-serif; }
        
        .navbar { background-color: var(--teal-color) !important; padding: 15px 0; }
        
        .unit-card { 
            border-radius: 15px; 
            transition: 0.3s; 
            border: none; 
            overflow: hidden; 
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .unit-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        .unit-image-wrapper { height: 200px; width: 100%; overflow: hidden; position: relative; }
        .unit-image-wrapper img { width: 100%; height: 100%; object-fit: cover; }

        .status-badge {
            position: absolute; top: 15px; right: 15px;
            background-color: #2ecc71; color: white;
            padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; font-weight: bold;
        }

        .price-tag { color: var(--teal-color); font-size: 1.4rem; font-weight: 800; }
        
        .btn-view-details {
            border: 2px solid var(--teal-color);
            color: var(--teal-color);
            font-weight: bold;
            border-radius: 10px;
            transition: 0.3s;
        }
        .btn-view-details:hover { background-color: var(--teal-color); color: white; }

        .modal-content { border-radius: 20px; border: none; overflow: hidden; }
        .amenity-pill {
            background: var(--light-teal);
            color: var(--teal-color);
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 0.85rem;
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 10px;
        }
        .btn-proceed {
            background-color: var(--teal-color);
            color: white;
            padding: 12px;
            font-weight: bold;
            border-radius: 10px;
            width: 100%;
            border: none;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-proceed:hover { background-color: #006666; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            MG APARTMENT
        </a>
        <div class="ms-auto">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="<?php echo ($_SESSION['role'] == 'admin') ? 'admin_dashboard.php' : 'tenant_dashboard.php'; ?>" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                    <i class="fa fa-th-large me-1"></i> My Dashboard
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline-light btn-sm px-3 rounded-pill">
                    <i class="fa fa-sign-in-alt me-1"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold" style="color: var(--teal-color);">Find Your Perfect Space</h1>
        <p class="text-muted">Modern rental units designed for your comfort.</p>
    </div>

    <div class="row g-4">
        <?php if(mysqli_num_rows($result) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4">
                    <div class="card h-100 unit-card">
                        <div class="unit-image-wrapper">
                            <img src="units/<?php echo htmlspecialchars($row['image_name'] ?: 'default.png'); ?>" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image+Available';">
                            <div class="status-badge">Available</div>
                        </div>
                        
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-1">Unit <?php echo htmlspecialchars($row['unit_no']); ?></h5>
                            <div class="price-tag mb-3">₱<?php echo number_format($row['monthly_rent'], 2); ?><span style="font-size: 0.8rem; color: #888;"> / Month</span></div>
                            
                            <button type="button" class="btn btn-view-details w-100 py-2" data-bs-toggle="modal" data-bs-target="#unitModal<?php echo $row['id']; ?>">
                                <i class="fa fa-info-circle me-2"></i>View Full Details
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="unitModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <img src="units/<?php echo htmlspecialchars($row['image_name'] ?: 'default.png'); ?>" class="img-fluid" style="height: 250px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/400x300?text=No+Image+Available';">
                            <div class="modal-body p-4">
                                <h3 class="fw-bold" style="color: var(--teal-color);">Unit <?php echo htmlspecialchars($row['unit_no']); ?></h3>
                                <p class="text-muted fw-bold mb-4"><?php echo htmlspecialchars($row['unit_type']); ?></p>
                                <hr>
                                <h6 class="fw-bold"><i class="fa fa-list me-2"></i>Description:</h6>
                                <p class="text-muted mb-4"><?php echo htmlspecialchars($row['description']); ?></p>

                                <h6 class="fw-bold mb-3"><i class="fa fa-star me-2"></i>Amenities:</h6>
                                <div class="mb-4">
                                    <span class="amenity-pill"><i class="fa fa-wifi me-1"></i> Free WiFi</span>
                                    <span class="amenity-pill"><i class="fa fa-video me-1"></i> CCTV</span>
                                    <span class="amenity-pill"><i class="fa fa-bolt me-1"></i> Submetered</span>
                                </div>

                                <?php 
                                    // ang logic neto Pag logged in, dashboard. Pag hindi, register.
                                    $link = isset($_SESSION['user_id']) ? "tenant_dashboard.php?request_unit=" . $row['id'] : "register.php?unit_id=" . $row['id'];
                                ?>
                                
                                <a href="<?php echo $link; ?>" class="btn btn-proceed text-uppercase">Proceed to Rent</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fa fa-house-circle-exclamation fa-4x mb-3 text-muted"></i>
                <h4 class="text-muted">No units currently available.</h4>
                <p>Please check back again later!</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>