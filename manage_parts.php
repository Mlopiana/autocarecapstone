<?php 
session_start();
require 'db_config.php';

// Ensure only shop admins can access
if ($_SESSION['role'] != 'shop_admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$query = $conn->prepare("SELECT shop_id FROM repair_shops WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$shop = $result->fetch_assoc();
$shop_id = $shop['shop_id'] ?? null;

if (!$shop_id) {
    die("No shop found for this admin.");
}

// --- Reusable Functions ---
function uploadPhoto($fileInput, $targetDir = "uploads/parts/") {
    if (!empty($_FILES[$fileInput]['name'])) {
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $photo = time() . "_" . basename($_FILES[$fileInput]['name']);
        $targetFile = $targetDir . $photo;
        move_uploaded_file($_FILES[$fileInput]['tmp_name'], $targetFile);
        return $photo;
    }
    return null;
}

function addPart($conn, $shop_id, $name, $price, $availability, $photo) {
    $stmt = $conn->prepare("INSERT INTO parts (shop_id, part_name, price, availability, photo) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdis", $shop_id, $name, $price, $availability, $photo);
    return $stmt->execute();
}

function updatePart($conn, $id, $shop_id, $name, $price, $availability, $photo = null) {
    if ($photo) {
        $stmt = $conn->prepare("UPDATE parts 
                                SET part_name=?, price=?, availability=?, photo=? 
                                WHERE part_id=? AND shop_id=?");
        $stmt->bind_param("sdissi", $name, $price, $availability, $photo, $id, $shop_id);
    } else {
        $stmt = $conn->prepare("UPDATE parts 
                                SET part_name=?, price=?, availability=? 
                                WHERE part_id=? AND shop_id=?");
        $stmt->bind_param("sdiii", $name, $price, $availability, $id, $shop_id);
    }
    return $stmt->execute();
}

function deletePart($conn, $id, $shop_id) {
    // Remove photo file if exists
    $stmt = $conn->prepare("SELECT photo FROM parts WHERE part_id=? AND shop_id=?");
    $stmt->bind_param("ii", $id, $shop_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res && $res['photo'] && file_exists("uploads/parts/" . $res['photo'])) {
        unlink("uploads/parts/" . $res['photo']);
    }

    $stmt = $conn->prepare("DELETE FROM parts WHERE part_id=? AND shop_id=?");
    $stmt->bind_param("ii", $id, $shop_id);
    return $stmt->execute();
}

// --- Handle CRUD actions ---
// Add Part
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_part'])) {
    $name = trim($_POST['part_name']);
    $price = floatval($_POST['price']);
    $availability = isset($_POST['availability']) ? 1 : 0;
    $photo = uploadPhoto('photo');

    addPart($conn, $shop_id, $name, $price, $availability, $photo);
    header("Location: manage_parts.php");
    exit();
}

// Update Part
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_part'])) {
    $id = intval($_POST['part_id']);
    $name = trim($_POST['part_name']);
    $price = floatval($_POST['price']);
    $availability = isset($_POST['availability']) ? 1 : 0;
    $photo = uploadPhoto('photo');

    updatePart($conn, $id, $shop_id, $name, $price, $availability, $photo);
    header("Location: manage_parts.php");
    exit();
}

// Delete Part
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    deletePart($conn, $id, $shop_id);
    header("Location: manage_parts.php");
    exit();
}

// --- Fetch all parts ---
$stmt = $conn->prepare("SELECT * FROM parts WHERE shop_id = ?");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$parts = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Parts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/shop_navbar.php'; ?>

<div class="container mt-5">
    <h2>Manage Parts</h2>

    <!-- Add Part Form -->
    <div class="card mb-4">
        <div class="card-header">Add New Part</div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Part Name</label>
                    <input type="text" name="part_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price (₱)</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="availability" class="form-check-input" id="availableAdd">
                    <label for="availableAdd" class="form-check-label">Available</label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Upload Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <button type="submit" name="add_part" class="btn btn-success">Add Part</button>
            </form>
        </div>
    </div>

    <!-- Parts List -->
    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Photo</th>
                <th>Part Name</th>
                <th>Price</th>
                <th>Available</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $parts->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if ($row['photo']): ?>
                        <img src="uploads/parts/<?= htmlspecialchars($row['photo']) ?>" width="60" height="60" class="rounded">
                    <?php else: ?>
                        <span class="text-muted">No photo</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['part_name']) ?></td>
                <td>₱<?= number_format($row['price'], 2) ?></td>
                <td><?= $row['availability'] ? 'Yes' : 'No' ?></td>
                <td>
                    <!-- Edit Button -->
                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['part_id'] ?>">Edit</button>
                    <a href="?delete=<?= $row['part_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this part?')">Delete</a>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['part_id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Part</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="part_id" value="<?= $row['part_id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Part Name</label>
                            <input type="text" name="part_name" class="form-control" value="<?= htmlspecialchars($row['part_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $row['price'] ?>" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" name="availability" class="form-check-input" id="available<?= $row['part_id'] ?>" <?= $row['availability'] ? 'checked' : '' ?>>
                            <label for="available<?= $row['part_id'] ?>" class="form-check-label">Available</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Change Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                            <?php if ($row['photo']): ?>
                                <img src="uploads/parts/<?= htmlspecialchars($row['photo']) ?>" width="80" class="mt-2 rounded">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" name="update_part" class="btn btn-primary">Save Changes</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
