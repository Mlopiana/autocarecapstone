<?php    
session_start();
require 'db_config.php';

// ✅ Require login to access this page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Shop not specified.");
}

$shop_id = intval($_GET['id']);

// Get shop info
$shop = $conn->query("SELECT * FROM repair_shops WHERE shop_id = $shop_id")->fetch_assoc();
if (!$shop) {
    die("Shop not found.");
}

$success_message = "";

// ✅ Handle booking form submission (Multiple Services)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_appointment'])) {
    $user_id = $_SESSION['user_id'];
    $service_ids = $_POST['service_ids'] ?? [];
    $date = $_POST['appointment_date'];
    $time = $_POST['appointment_time'];

    if (!empty($service_ids)) {
        foreach ($service_ids as $service_id) {
            $stmt = $conn->prepare("INSERT INTO appointments (user_id, shop_id, service_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iiiss", $user_id, $shop_id, $service_id, $date, $time);
            $stmt->execute();
        }
        $success_message = "Appointment booked successfully for selected services!";
    } else {
        $success_message = "Please select at least one service.";
    }
}

// ✅ Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $stmt = $conn->prepare("INSERT INTO reviews (user_id, shop_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $shop_id, $rating, $comment);
    $stmt->execute();
    $success_message = "Review submitted successfully!";
}

// ✅ Fetch data
$services = $conn->query("SELECT * FROM services WHERE shop_id = $shop_id");
$reviews = $conn->query("
    SELECT r.rating, r.comment, r.created_at, u.name AS customer_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.shop_id = $shop_id
    ORDER BY r.created_at DESC
");
$parts = $conn->query("SELECT * FROM parts WHERE shop_id = $shop_id");

$lat = $shop['latitude'];
$lng = $shop['longitude'];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($shop['shop_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: url('garage-bg.jpg') no-repeat center center/cover;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            background: #2c2c2c;
            padding: 10px 20px;
            text-align: right;
            border-bottom: 4px solid #ffcc00;
        }
        .top-bar a {
            background: #ffcc00;
            color: #000;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
        }
        .top-bar a:hover {
            background: #e6b800;
        }
        .shop-container {
            max-width: 1100px;
            margin: 40px auto;
            background: rgba(20,20,20,0.95);
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.7);
            color: #ecf0f1;
        }
        h2, h4 { color: #f39c12; text-transform: uppercase; }
        label { font-weight: bold; color: #ecf0f1; }
        input, select, textarea {
            background: #2c3e50; color: #ecf0f1;
            border: 1px solid #7f8c8d; border-radius: 8px; padding: 10px;
        }
        button {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: #fff; border: none; border-radius: 10px; font-weight: bold;
        }
        button:hover { background: linear-gradient(135deg, #d35400, #e67e22); }
        .card { background: #2c3e50; color: #ecf0f1; border: none; }
        .card img { border-radius: 8px 8px 0 0; }
        .badge { font-weight: bold; }
        .alert-success { background: #2ecc71; color: #fff; }
        a { color: #f39c12; text-decoration: none; }
        a:hover { color: #e67e22; }
        #map { border-radius: 8px; }
        .service-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .service-item label { margin-left: 8px; }
    </style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <a href="customer_dashboard.php">← Back to Dashboard</a>
</div>

<div class="shop-container">
    <!-- Return and Dashboard Buttons -->
    <div class="mb-3">
        <button onclick="history.back()" class="btn btn-outline-light">← Return</button>
       
    </div>

    <h2><?= htmlspecialchars($shop['shop_name']) ?></h2>
    <p><?= htmlspecialchars($shop['description'] ?? "No description available.") ?></p>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <!-- Map Section -->
    <div class="mt-4">
        <h4>Shop Location</h4>
        <div id="map" style="height: 400px;"></div>
    </div>

    <!-- Booking Section -->
    <div class="mt-4">
        <h4>Book an Appointment</h4>
        <?php if ($services->num_rows > 0): ?>
            <form method="POST">
                <div class="mb-3">
                    <label>Select Services</label><br>
                    <?php while ($s = $services->fetch_assoc()): ?>
                        <div class="service-item">
                            <div>
                                <input type="checkbox" name="service_ids[]" value="<?= $s['service_id'] ?>">
                                <label><?= htmlspecialchars($s['service_name']) ?></label>
                            </div>
                            <span>₱<?= number_format($s['price'], 2) ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="mb-3">
                    <label>Date</label>
                    <input type="date" name="appointment_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Time</label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
                <button type="submit" name="book_appointment">Book Now</button>
            </form>
        <?php else: ?>
            <p>No services available at this shop.</p>
        <?php endif; ?>
    </div>

    <!-- Parts Section -->
    <div class="mt-5">
        <h4>Available Parts & Products</h4>
        <?php if ($parts->num_rows > 0): ?>
            <div class="row">
                <?php while ($p = $parts->fetch_assoc()): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm h-100">
                            <?php if (!empty($p['photo'])): ?>
                                <img src="uploads/parts/<?= htmlspecialchars($p['photo']) ?>" class="card-img-top" style="height:200px; object-fit:cover;">
                            <?php else: ?>
                                <img src="assets/no-image.png" class="card-img-top" style="height:200px; object-fit:cover;">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($p['part_name']) ?></h5>
                                <p class="card-text">₱<?= number_format($p['price'], 2) ?></p>
                                <span class="badge <?= $p['availability'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $p['availability'] ? 'Available' : 'Out of Stock' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No parts/products available at this shop.</p>
        <?php endif; ?>
    </div>

    <!-- Reviews -->
    <div class="mt-5">
        <h4>Customer Reviews</h4>
        <?php if ($reviews->num_rows > 0): ?>
            <?php while ($rev = $reviews->fetch_assoc()): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($rev['customer_name']) ?> 
                            <small class="text-muted">(<?= $rev['rating'] ?>/5)</small>
                        </h6>
                        <p class="card-text"><?= htmlspecialchars($rev['comment']) ?></p>
                        <small class="text-muted"><?= $rev['created_at'] ?></small>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reviews yet.</p>
        <?php endif; ?>
    </div>

    <!-- Leave a Review -->
    <div class="mt-4">
        <h4>Leave a Review</h4>
        <form method="POST">
            <div class="mb-3">
                <label>Rating</label>
                <select name="rating" class="form-select" required>
                    <option value="">Select rating</option>
                    <option value="5">⭐⭐⭐⭐⭐</option>
                    <option value="4">⭐⭐⭐⭐</option>
                    <option value="3">⭐⭐⭐</option>
                    <option value="2">⭐⭐</option>
                    <option value="1">⭐</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Comment</label>
                <textarea name="comment" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" name="submit_review" class="btn btn-success">Submit Review</button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    var shopLat = <?= json_encode($lat) ?>;
    var shopLng = <?= json_encode($lng) ?>;

    var map = L.map('map').setView([shopLat, shopLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    var popupContent = `
        <div style="text-align:center;">
            <strong><?= htmlspecialchars($shop['shop_name']) ?></strong><br>
            <a href="https://www.google.com/maps/dir/?api=1&destination=${shopLat},${shopLng}" 
               target="_blank" 
               class="btn btn-sm btn-primary mt-2">
               Navigate
            </a>
        </div>
    `;

    L.marker([shopLat, shopLng]).addTo(map)
        .bindPopup(popupContent)
        .openPopup();
</script>

</body>
</html>
