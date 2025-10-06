<?php    
session_start();
require 'db_config.php';

if ($_SESSION['role'] != 'shop_admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$shopQuery = $conn->prepare("SELECT shop_id FROM repair_shops WHERE user_id = ?");
$shopQuery->bind_param("i", $user_id);
$shopQuery->execute();
$shop_id = $shopQuery->get_result()->fetch_assoc()['shop_id'];

// Handle Accept/Cancel/Finish actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['appointment_id'], $_POST['action'])) {
    $appointment_id = $_POST['appointment_id'];
    $action = $_POST['action'];

    if ($action === "accept") {
        $status = "confirmed";
        $message = "Your appointment has been accepted!";

        $update = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $update->bind_param("si", $status, $appointment_id);
        $update->execute();

    } elseif ($action === "cancel") {
        $status = "cancelled";
        $message = "Your appointment has been cancelled.";

        $update = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
        $update->bind_param("si", $status, $appointment_id);
        $update->execute();

    } elseif ($action === "finish") {
        // ✅ Keep status confirmed, only set finished_at
        $message = "Your appointment has been finished.";

        $update = $conn->prepare("UPDATE appointments SET finished_at = NOW() WHERE appointment_id = ?");
        $update->bind_param("i", $appointment_id);
        $update->execute();
    }

    // Fetch customer ID for notification
    $userStmt = $conn->prepare("SELECT user_id FROM appointments WHERE appointment_id = ?");
    $userStmt->bind_param("i", $appointment_id);
    $userStmt->execute();
    $user_id_result = $userStmt->get_result()->fetch_assoc()['user_id'];

    // Insert notification for customer
    $notif = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $notif->bind_param("is", $user_id_result, $message);
    $notif->execute();
}

// Fetch appointments
$stmt = $conn->prepare("
    SELECT a.*, u.name AS customer_name, s.service_name 
    FROM appointments a
    JOIN users u ON a.user_id = u.user_id
    JOIN services s ON a.service_id = s.service_id
    WHERE a.shop_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->bind_param("i", $shop_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body>
<?php include 'includes/shop_navbar.php'; ?>
<div class="container mt-5">
    <h2>Appointments</h2>
    <table id="appointmentsTable" class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Customer</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Finished At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $appointments->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['service_name']) ?></td>
                <td><?= $row['appointment_date'] ?></td>
                <td><?= $row['appointment_time'] ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= $row['finished_at'] ? $row['finished_at'] : '—' ?></td>
                <td>
                    <?php if ($row['status'] == "pending"): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                            <button type="button" class="btn btn-success btn-sm accept-btn" data-id="<?= $row['appointment_id'] ?>">Accept</button>
                            <button type="submit" name="action" value="cancel" class="btn btn-danger btn-sm">Cancel</button>
                        </form>
                    <?php elseif ($row['status'] == "confirmed" && !$row['finished_at']): ?>
                        <button type="button" 
                                class="btn btn-secondary btn-sm mark-finished-btn" 
                                data-id="<?= $row['appointment_id'] ?>" 
                                data-date="<?= $row['appointment_date'] ?>" 
                                data-time="<?= $row['appointment_time'] ?>">
                            Mark as Finished
                        </button>
                    <?php else: ?>
                        <span class="text-muted">No actions</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>

<!-- ✅ Accept Confirmation Modal -->
<div class="modal fade" id="acceptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Accept</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to accept this appointment?
        </div>
        <div class="modal-footer">
          <input type="hidden" name="appointment_id" id="acceptAppointmentId">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="action" value="accept" class="btn btn-success">Yes, Accept</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ✅ Confirm Finish Modal -->
<div class="modal fade" id="finishModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Confirm Finish</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to mark this appointment as finished?
        </div>
        <div class="modal-footer">
          <input type="hidden" name="appointment_id" id="finishAppointmentId">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
          <button type="submit" name="action" value="finish" class="btn btn-success">Yes, Finish</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ❌ Notice Modal -->
<div class="modal fade" id="noticeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Too Early</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        The appointment cannot be marked as finished yet. The scheduled time has not passed.
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- ✅ jQuery + DataTables + Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    $('#appointmentsTable').DataTable({
        "pageLength": 10,
        "order": [[ 2, "desc" ]] // default sort by Date column
    });

    // ✅ Accept button -> open modal
    document.querySelectorAll(".accept-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const apptId = this.getAttribute("data-id");
            document.getElementById("acceptAppointmentId").value = apptId;
            new bootstrap.Modal(document.getElementById("acceptModal")).show();
        });
    });

    // ✅ Finish button -> check time
    document.querySelectorAll(".mark-finished-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            const apptId = this.getAttribute("data-id");
            const apptDate = this.getAttribute("data-date");
            const apptTime = this.getAttribute("data-time");

            const scheduled = new Date(`${apptDate}T${apptTime}`);
            const now = new Date();

            if (now >= scheduled) {
                document.getElementById("finishAppointmentId").value = apptId;
                new bootstrap.Modal(document.getElementById("finishModal")).show();
            } else {
                new bootstrap.Modal(document.getElementById("noticeModal")).show();
            }
        });
    });
});
</script>
</body>
</html>
