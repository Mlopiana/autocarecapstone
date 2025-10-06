<?php 
session_start();
require 'db_config.php';

if ($_SESSION['role'] != 'shop_admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle AJAX status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $shop_id = intval($_POST['shop_id']);
    $status = strtolower($_POST['status']);

    if (!in_array($status, ['open','busy','closed'])) {
        echo json_encode(['success'=>false, 'message'=>'Invalid status']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE repair_shops SET status=? WHERE shop_id=?");
    $stmt->bind_param("si", $status, $shop_id);

    if ($stmt->execute()) {
        echo json_encode(['success'=>true]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'Database error']);
    }
    exit(); // important to stop further output
}

// Get shop info
$shopQuery = $conn->prepare("SELECT * FROM repair_shops WHERE user_id = ?");
$shopQuery->bind_param("i", $user_id);
$shopQuery->execute();
$result = $shopQuery->get_result();
$shop = $result->fetch_assoc();

if (!$shop) {
    echo "No shop associated with your account.";
    exit();
}

$shop_id = $shop['shop_id'];

// Get counts
function getCount($conn, $table, $shop_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE shop_id = ?");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

$servicesTotal     = getCount($conn, 'services', $shop_id);
$partsTotal        = getCount($conn, 'parts', $shop_id);
$appointmentsTotal = getCount($conn, 'appointments', $shop_id);
$reviewsTotal      = getCount($conn, 'reviews', $shop_id);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shop Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f1ea; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #2c2c2c; }
        h2,h5 { font-weight: 600; font-family: 'Orbitron', sans-serif; letter-spacing: 1px; }
        .card { border-radius: 15px; box-shadow: 0px 4px 12px rgba(0,0,0,0.15); }
        .card-header { border-radius: 15px 15px 0 0 !important; font-weight: bold; background: linear-gradient(90deg,#2c2c2c,#1a1a1a); color: #ffcc00; font-family: 'Orbitron',sans-serif; letter-spacing:1px; }
        .stat-card { background: linear-gradient(135deg,#2c2c2c,#1a1a1a); color: #ffcc00; text-align:center; border:2px solid #ffcc00; }
        .stat-card h5 { font-size:1.1rem; }
        .stat-card p { font-size:2rem; margin:0; }
        .btn-custom { border-radius:10px; font-weight:600; font-family:'Orbitron',sans-serif; }
        .btn-outline-primary { border-color:#ffcc00; color:#2c2c2c; }
        .btn-outline-primary:hover { background:#ffcc00; color:#1a1a1a; }
    </style>
</head>
<body>
<?php include 'includes/shop_navbar.php'; ?>

<div class="container mt-5">
    <h2 class="mb-3">Welcome, <?php echo $_SESSION['name']; ?>!</h2>
    <p class="mb-4">Here is your shop overview and management panel.</p>

    <!-- Shop Info -->
    <div class="card mb-4">
        <div class="card-header">Your Shop Info</div>
        <div class="card-body">
            <p><strong>Shop Name:</strong> <?php echo htmlspecialchars($shop['shop_name']); ?></p>
            <p><strong>Address:</strong> <?php echo htmlspecialchars($shop['address']); ?></p>
            <p><strong>Status:</strong> <span id="shop-status"><?php echo ucfirst($shop['status']); ?></span></p>
            <p><strong>Emergency Available:</strong> <?php echo $shop['emergency_available'] ? 'Yes' : 'No'; ?></p>
            <p><strong>Rating:</strong> <?php echo $shop['rating']; ?> ⭐</p>

            <!-- Status Buttons -->
            <div class="mt-2">
                <button class="btn btn-outline-success btn-sm me-2" onclick="changeStatus('open')">Open</button>
                <button class="btn btn-outline-warning btn-sm me-2" onclick="changeStatus('busy')">Busy</button>
                <button class="btn btn-outline-danger btn-sm" onclick="changeStatus('closed')">Closed</button>
            </div>
        </div>
    </div>

    <!-- Dashboard Stats -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5>Services</h5>
                    <p><?php echo $servicesTotal; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5>Parts</h5>
                    <p><?php echo $partsTotal; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5>Appointments</h5>
                    <p><?php echo $appointmentsTotal; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card">
                <div class="card-body">
                    <h5>Reviews</h5>
                    <p><?php echo $reviewsTotal; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="card">
        <div class="card-header">Manage Your Shop</div>
        <div class="card-body">
            <a href="manage_services.php" class="btn btn-outline-primary btn-custom me-2">Manage Services</a>
            <a href="manage_parts.php" class="btn btn-outline-primary btn-custom me-2">Manage Parts</a>
            <a href="view_appointments.php" class="btn btn-outline-primary btn-custom me-2">View Appointments</a>
            <a href="view_reviews.php" class="btn btn-outline-primary btn-custom me-2">Customer Reviews</a>
            <a href="view_reports.php" class="btn btn-outline-primary btn-custom">View Reports</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
function changeStatus(newStatus) {
    if(!confirm(`Are you sure you want to change the shop status to ${newStatus}?`)) return;

    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('shop_id', <?php echo $shop_id; ?>);
    formData.append('status', newStatus);

    fetch('', { method:'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            document.getElementById('shop-status').innerText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
            alert('Status updated successfully!');
        } else {
            alert('Failed to update status: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred while updating status.');
    });
}
</script>
</body>
</html>
