<?php
session_start();
require 'db_config.php';
if ($_SESSION['role'] != 'shop_admin') header("Location: login.php");

$user_id = $_SESSION['user_id'];
// Get the shop_id linked to the admin
$query = $conn->prepare("SELECT shop_id FROM repair_shops WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$shop_id = $query->get_result()->fetch_assoc()['shop_id'];

// Handle form submission to add a service
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $name = $_POST['service_name'];
    $desc = $_POST['description'];
    $price = $_POST['price'];

    if (!empty($name) && is_numeric($price)) {
        $stmt = $conn->prepare("INSERT INTO services (shop_id, service_name, description, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $shop_id, $name, $desc, $price);
        $stmt->execute();
    }
    header("Location: manage_services.php");
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM services WHERE service_id = $id AND shop_id = $shop_id");
    header("Location: manage_services.php");
    exit;
}

// Fetch all services for display
$stmt = $conn->prepare("SELECT * FROM services WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$services = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/shop_navbar.php'; ?>

<div class="container mt-5">
    <h2 class="mb-4">Manage Services</h2>

    <!-- Add Service Form -->
    <form method="POST" class="mb-4">
        <div class="row g-2">
            <div class="col-md-3">
                <input type="text" name="service_name" class="form-control" placeholder="Service Name" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="description" class="form-control" placeholder="Description">
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required>
            </div>
            <div class="col-md-3">
                <button type="submit" name="add_service" class="btn btn-primary w-100">Add Service</button>
            </div>
        </div>
    </form>

    <!-- Services Table -->
    <table class="table table-bordered table-hover">
        <thead>
            <tr><th>Service Name</th><th>Description</th><th>Price</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($row = $services->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['service_name']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td>₱<?= number_format($row['price'], 2) ?></td>
                <td>
                    <a href="edit_service.php?id=<?= $row['service_id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="manage_services.php?delete=<?= $row['service_id'] ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
