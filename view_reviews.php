<?php
session_start();
require 'db_config.php';
if ($_SESSION['role'] != 'shop_admin') header("Location: login.php");

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT shop_id FROM repair_shops WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$shop_id = $query->get_result()->fetch_assoc()['shop_id'];

$stmt = $conn->prepare("
    SELECT r.*, u.name AS customer_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.shop_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$reviews = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head><title>Customer Reviews</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<?php include 'includes/shop_navbar.php'; ?>
<div class="container mt-5">
    <h2>Customer Reviews</h2>
    <table class="table">
        <thead><tr><th>Customer</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
        <tbody>
        <?php while ($row = $reviews->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= $row['rating'] ?> ⭐</td>
                <td><?= htmlspecialchars($row['comment']) ?></td>
                <td><?= $row['created_at'] ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
