<nav class="navbar navbar-expand-lg sticky-top" style="background: linear-gradient(90deg, #2c2c2c, #1a1a1a); border-bottom: 4px solid #ffcc00;">
  <div class="container-fluid">
    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center text-warning fw-bold" href="shop_dashboard.php" style="font-family: 'Orbitron', sans-serif; letter-spacing: 2px;">
      <i class="bi bi-gear-fill me-2"></i> AutoCare
    </a>

    <!-- Mobile Toggler -->
    <button class="navbar-toggler text-warning border-warning" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menu Links -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="shop_dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="view_appointments.php"><i class="bi bi-calendar-check me-1"></i> Appointments</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="manage_services.php"><i class="bi bi-tools me-1"></i> Services</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="manage_parts.php"><i class="bi bi-nut-fill me-1"></i> Parts</a>
        </li>
        <!-- ✅ New Links -->
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="view_reviews.php"><i class="bi bi-chat-dots me-1"></i> Reviews</a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-light fw-semibold" href="view_reports.php"><i class="bi bi-bar-chart-line me-1"></i> Reports</a>
        </li>
      </ul>

      <!-- Right-side (User & Logout) -->
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <!-- User button opens modal -->
          <button type="button" class="btn btn-sm btn-outline-warning fw-bold me-3" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['name'] ?? 'Guest'; ?>
          </button>
        </li>
        <li class="nav-item">
          <a class="btn btn-warning btn-sm fw-bold" href="login.php">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- User Info Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:#2c2c2c; color:white; border: 2px solid #ffcc00;">
      <div class="modal-header">
        <h5 class="modal-title text-warning" id="userModalLabel"><i class="bi bi-person-badge"></i> User Profile</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Name:</strong> <?php echo $_SESSION['name'] ?? 'N/A'; ?></p>
        <p><strong>Email:</strong> <?php echo $_SESSION['email'] ?? 'N/A'; ?></p>
        <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'N/A'; ?></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-warning" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Load Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<!-- Mechanical font -->
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
<!-- ✅ Bootstrap JS Bundle (makes navbar toggler & modals work) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
