<?php
session_start();
require 'db_config.php';

if ($_SESSION['role'] != 'shop_admin') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the shop_id for this admin
$query = $conn->prepare("SELECT shop_id FROM repair_shops WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$shop_id = $query->get_result()->fetch_assoc()['shop_id'];

// Retrieve confirmed appointments with customer and service info
$stmt = $conn->prepare("
    SELECT a.appointment_id, u.name AS customer_name, s.service_name, s.price, a.appointment_date
    FROM appointments a
    LEFT JOIN users u ON a.user_id = u.user_id
    LEFT JOIN services s ON a.service_id = s.service_id
    WHERE a.shop_id = ? AND a.status = 'confirmed'
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Calculate total potential profit
$total_profit = 0;
while ($row = $appointments->fetch_assoc()) {
    $total_profit += $row['price'];
}

// Reset pointer for table output
$appointments->data_seek(0);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/shop_navbar.php'; ?>
<div class="container mt-5">
    <h2>Confirmed Appointments & Potential Profit</h2>
    <p><strong>Total Possible Profit: ₱<?= number_format($total_profit, 2) ?></strong></p>
    
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Appointment ID</th>
                <th>Customer Name</th>
                <th>Service</th>
                <th>Price (₱)</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?= $row['appointment_id'] ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? 'Unknown') ?></td>
                <td><?= htmlspecialchars($row['service_name'] ?? 'Unknown') ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td><?= $row['appointment_date'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
