<?php
include('db.php');
session_start();

// SECURITY: Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- LOGIC: ADD NEW UNIT ---
if (isset($_POST['add_unit'])) {
    $unit_no = mysqli_real_escape_string($conn, $_POST['unit_no']);
    $rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $unit_type = mysqli_real_escape_string($conn, $_POST['unit_type']);
    $floor_area = mysqli_real_escape_string($conn, $_POST['floor_area']);
    $amenities = mysqli_real_escape_string($conn, $_POST['amenities']);
    $status = "Available";

    $img_name = $_FILES['unit_image']['name'];
    $tmp_name = $_FILES['unit_image']['tmp_name'];
    $target_dir = "uploads/units/";
    
    // Create directory if not exists
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    $final_img = time() . "_" . $img_name;
    
    if (move_uploaded_file($tmp_name, $target_dir . $final_img)) {
        $sql = "INSERT INTO units (unit_no, unit_type, floor_area, monthly_rent, description, amenities, status, image_name) 
                VALUES ('$unit_no', '$unit_type', '$floor_area', '$rent', '$desc', '$amenities', '$status', '$final_img')";
        
        if (mysqli_query($conn, $sql)) {
            header("Location: manage_units.php?msg=Unit added successfully!");
            exit();
        }
    }
}

// --- LOGIC: UPDATE UNIT ---
if (isset($_POST['update_unit'])) {
    $id = $_POST['unit_id'];
    $unit_no = mysqli_real_escape_string($conn, $_POST['unit_no']);
    $rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $unit_type = mysqli_real_escape_string($conn, $_POST['unit_type']);
    $floor_area = mysqli_real_escape_string($conn, $_POST['floor_area']);
    $amenities = mysqli_real_escape_string($conn, $_POST['amenities']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $update_sql = "UPDATE units SET unit_no='$unit_no', unit_type='$unit_type', floor_area='$floor_area', 
                   monthly_rent='$rent', description='$desc', amenities='$amenities', status='$status' WHERE id='$id'";
    
    if (!empty($_FILES['unit_image']['name'])) {
        $img_name = time() . "_" . $_FILES['unit_image']['name'];
        move_uploaded_file($_FILES['unit_image']['tmp_name'], "uploads/units/" . $img_name);
        mysqli_query($conn, "UPDATE units SET image_name='$img_name' WHERE id='$id'");
    }

    if (mysqli_query($conn, $update_sql)) {
        header("Location: manage_units.php?msg=Unit updated successfully!");
        exit();
    }
}

// FETCH ALL UNITS
$units = mysqli_query($conn, "SELECT * FROM units ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Units - MG's Apartment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; color: #333; }
        
        /* Top Header */
        .header-top { background: white; padding: 12px 0; border-bottom: 1px solid #eee; margin-bottom: 25px; }
        .brand-name { color: #008080; font-weight: 800; font-size: 1.3rem; letter-spacing: 1px; }
        .brand-name span { color: #333; }
        .back-link { color: #008080; text-decoration: none; font-size: 0.85rem; font-weight: 600; }
        
        /* Table Zoom Effect */
        .table tbody tr { transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
        .table tbody tr:hover { 
            transform: scale(1.01); 
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            background-color: #fff !important;
            z-index: 5;
            position: relative;
        }

        .unit-img-table { width: 60px; height: 45px; object-fit: cover; border-radius: 6px; }
        .btn-teal { background: #008080; color: white; border: none; font-size: 0.85rem; }
        .btn-teal:hover { background: #006666; color: white; }
        
        .unit-no-bold { font-weight: 700; color: #1a1a1a; font-size: 0.95rem; }
        .badge-available { background: #dcfce7; color: #16a34a; font-size: 0.7rem; }
        .badge-occupied { background: #fee2e2; color: #dc2626; font-size: 0.7rem; }
    </style>
</head>
<body>

<div class="header-top sticky-top shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="admin_dashboard.php" class="back-link"><i class="fa fa-arrow-left me-2"></i>Back to Dashboard</a>
        <div class="brand-name">MG's <span>APARTMENT</span></div>
        <button class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUnitModal">
            <i class="fa fa-plus me-2"></i>Add New Unit
        </button>
    </div>
</div>

<div class="container pb-5">
    <div class="mb-4">
        <h4 class="fw-bold m-0">Rental Units Management</h4>
        <p class="text-muted small">Overview of all units, pricing, and availability.</p>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4 small rounded-3"><i class="fa fa-check-circle me-1"></i> <?php echo $_GET['msg']; ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr style="font-size: 0.8rem; text-transform: uppercase; color: #666;">
                        <th class="ps-4 py-3">Unit / Photo</th>
                        <th>Type & Size</th>
                        <th>Monthly Rent</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($units)): 
                        $status_class = ($row['status'] == 'Available') ? 'badge-available' : 'badge-occupied';
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <img src="units/<?php echo htmlspecialchars($row['image_name'] ?: 'default.png'); ?>" class="unit-img-table me-3 shadow-sm border">
                                <span class="unit-no-bold"><?php echo $row['unit_no']; ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="fw-600" style="font-size: 0.85rem;"><?php echo $row['unit_type']; ?></div>
                            <small class="text-muted"><?php echo $row['floor_area']; ?></small>
                        </td>
                        <td class="fw-bold" style="color: #008080;">₱<?php echo number_format($row['monthly_rent'], 2); ?></td>
                        <td><span class="badge rounded-pill <?php echo $status_class; ?>"><?php echo strtoupper($row['status']); ?></span></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-light border text-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>">
                                <i class="fa fa-edit"></i>
                            </button>
                            <a href="delete_unit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-light border text-danger" onclick="return confirm('Archive this unit?')">
                                <i class="fa fa-archive"></i>
                            </a>
                        </td>
                    </tr>

                    <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <form action="manage_units.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-content border-0 shadow rounded-4">
                                    <div class="modal-header border-0 p-4 pb-0">
                                        <h5 class="fw-bold">Update Unit #<?php echo $row['unit_no']; ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <input type="hidden" name="unit_id" value="<?php echo $row['id']; ?>">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Unit No.</label>
                                                <input type="text" name="unit_no" class="form-control" value="<?php echo $row['unit_no']; ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold">Monthly Rent (₱)</label>
                                                <input type="number" name="monthly_rent" class="form-control" value="<?php echo $row['monthly_rent']; ?>" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold">Type</label>
                                                <select name="unit_type" class="form-select">
                                                    <option value="Studio" <?php if($row['unit_type']=='Studio') echo 'selected'; ?>>Studio</option>
                                                    <option value="1BR" <?php if($row['unit_type']=='1BR') echo 'selected'; ?>>1BR</option>
                                                    <option value="2BR" <?php if($row['unit_type']=='2BR') echo 'selected'; ?>>2BR</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold">Floor Area</label>
                                                <input type="text" name="floor_area" class="form-control" value="<?php echo $row['floor_area']; ?>">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-bold">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="Available" <?php if($row['status']=='Available') echo 'selected'; ?>>Available</option>
                                                    <option value="Occupied" <?php if($row['status']=='Occupied') echo 'selected'; ?>>Occupied</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Amenities</label>
                                                <input type="text" name="amenities" class="form-control" value="<?php echo $row['amenities']; ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Change Image (Leave blank to keep current)</label>
                                                <input type="file" name="unit_image" class="form-control">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-bold">Description</label>
                                                <textarea name="description" class="form-control" rows="2"><?php echo $row['description']; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 p-4 pt-0">
                                        <button type="submit" name="update_unit" class="btn btn-teal w-100 py-2 rounded-3">Save Changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addUnitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="manage_units.php" method="POST" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 p-4 pb-0"><h5 class="fw-bold">Add New Rental Unit</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label small fw-bold">Unit No.</label><input type="text" name="unit_no" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Monthly Rent</label><input type="number" name="monthly_rent" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Type</label><select name="unit_type" class="form-select"><option>Studio</option><option>1BR</option><option>2BR</option></select></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Floor Area</label><input type="text" name="floor_area" class="form-control" placeholder="e.g. 20sqm"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Amenities</label><input type="text" name="amenities" class="form-control"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Photo</label><input type="file" name="unit_image" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-bold">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0"><button type="submit" name="add_unit" class="btn btn-teal w-100 py-2">Add Unit</button></div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>