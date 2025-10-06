<?php   
session_start();
require 'db_config.php';

// Ensure only customers can access
if ($_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- ✅ DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: url('garage-bg.jpg') no-repeat center center/cover;
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        h2, h4 {
            color: #f39c12;
            font-weight: bold;
        }

        .card {
            background-color: #2c3e50;
            color: #ecf0f1;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: scale(1.02);
        }

        .card-header {
            background: #34495e;
            font-weight: bold;
            color: #f39c12;
        }

        .card-body {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 250px;
        }

        .table thead th {
            position: sticky;
            top: 0;
            background: #34495e;
            z-index: 2;
        }

        .table {
            width: 100%;
            min-width: 500px;
            border-collapse: collapse;
        }

        @media (max-width: 768px) {
            .table th, .table td {
                font-size: 0.85rem;
                white-space: nowrap;
            }

            .card-body {
                max-height: 180px;
            }
        }

        .expanded-view {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
        }

        .btn-back {
            background: #e67e22;
            border: none;
            margin-bottom: 15px;
        }

        .btn-back:hover {
            background: #d35400;
        }

        footer {
            background-color: #212529;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: auto;
            border-top: 4px solid #f39c12;
        }

        .part-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<main class="container py-4">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!</h2>

    <!-- Dashboard Grid -->
    <div id="dashboardGrid" class="row row-cols-1 row-cols-md-2 g-4 mt-3">

        <!-- ✅ Appointments Card -->
        <div class="col">
            <div class="card h-100" onclick="expandView('appointments')">
                <div class="card-header">Appointments</div>
                <div class="card-body">
                    <?php
                    $stmt = $conn->prepare("
                        SELECT a.*, s.service_name, r.shop_name
                        FROM appointments a
                        JOIN services s ON a.service_id = s.service_id
                        JOIN repair_shops r ON a.shop_id = r.shop_id
                        WHERE a.user_id = ?
                        ORDER BY a.appointment_date DESC
                        LIMIT 5
                    ");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $appointments = $stmt->get_result();

                    if ($appointments->num_rows > 0) {
                        echo "<div class='table-responsive'>
                                <table class='table table-sm table-bordered text-light align-middle mb-0'>
                                    <thead>
                                        <tr>
                                            <th>Shop</th>
                                            <th>Service</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>";
                        while ($row = $appointments->fetch_assoc()) {
                            echo "<tr>
                                    <td>{$row['shop_name']}</td>
                                    <td>{$row['service_name']}</td>
                                    <td>{$row['appointment_date']}</td>
                                    <td>{$row['appointment_time']}</td>
                                    <td>" . ucfirst($row['status']) . "</td>
                                  </tr>";
                        }
                        echo "</tbody></table></div>";
                    } else {
                        echo "<p>No appointments yet.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Repair Shops Card -->
        <div class="col">
            <div class="card h-100" onclick="expandView('shops')">
                <div class="card-header">Repair Shops</div>
                <div class="card-body">
                    <?php
                    $shops_preview = $conn->query("SELECT * FROM repair_shops LIMIT 3");
                    while ($shop = $shops_preview->fetch_assoc()) {
                        echo "<p><strong>{$shop['shop_name']}</strong> - {$shop['address']}</p>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Services Card -->
        <div class="col">
            <div class="card h-100" onclick="expandView('services')">
                <div class="card-header">Services</div>
                <div class="card-body">
                    <?php
                    $services_preview = $conn->query("SELECT * FROM services LIMIT 3");
                    while ($service = $services_preview->fetch_assoc()) {
                        echo "<p><strong>{$service['service_name']}</strong> - ₱{$service['price']}</p>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Parts Card -->
        <div class="col">
            <div class="card h-100" onclick="expandView('parts')">
                <div class="card-header">Parts</div>
                <div class="card-body">
                    <?php
                    $parts_preview = $conn->query("SELECT * FROM parts LIMIT 3");
                    while ($part = $parts_preview->fetch_assoc()) {
                        echo "<p><strong>{$part['part_name']}</strong> - ₱{$part['price']}</p>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Expanded View -->
    <div id="expandedView" class="d-none mt-4">
        <button class="btn btn-back text-light" onclick="closeExpand()">← Back</button>
        <div class="expanded-view" id="expandedContent"></div>
    </div>
</main>

<footer>
    <p>&copy; 2025 AutoCare. All rights reserved.</p>
</footer>

<!-- ✅ DataTables Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function expandView(section) {
    document.getElementById('dashboardGrid').classList.add('d-none');
    document.getElementById('expandedView').classList.remove('d-none');

    let content = "";

    switch(section) {
        case 'appointments':
            content = `<?php
                $stmt = $conn->prepare("
                    SELECT a.*, s.service_name, r.shop_name
                    FROM appointments a
                    JOIN services s ON a.service_id = s.service_id
                    JOIN repair_shops r ON a.shop_id = r.shop_id
                    WHERE a.user_id = ?
                    ORDER BY a.appointment_date DESC
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $appointments_full = $stmt->get_result();

                if ($appointments_full->num_rows > 0) {
                    $html = "<h4>All Appointments</h4>
                    <div class='table-responsive'>
                    <table id='appointmentsTable' class='table table-bordered text-light'>
                    <thead>
                        <tr><th>Shop</th><th>Service</th><th>Date</th><th>Time</th><th>Status</th></tr>
                    </thead>
                    <tbody>";
                    while ($row = $appointments_full->fetch_assoc()) {
                        $html .= "<tr>
                                    <td>{$row['shop_name']}</td>
                                    <td>{$row['service_name']}</td>
                                    <td>{$row['appointment_date']}</td>
                                    <td>{$row['appointment_time']}</td>
                                    <td>" . ucfirst($row['status']) . "</td>
                                  </tr>";
                    }
                    $html .= "</tbody></table></div>";
                    echo $html;
                } else {
                    echo "<p>No appointments yet.</p>";
                }
            ?>`;
            break;

        case 'shops':
            content = `<?php
                $shops_full = $conn->query("SELECT * FROM repair_shops");
                if ($shops_full->num_rows > 0) {
                    $html = "<h4>All Repair Shops</h4>
                    <div class='table-responsive'>
                    <table id='shopsTable' class='table table-bordered text-light'>
                    <thead><tr><th>Shop Name</th><th>Address</th><th>Action</th></tr></thead><tbody>";
                    while ($shop = $shops_full->fetch_assoc()) {
                        $html .= "<tr>
                            <td>{$shop['shop_name']}</td>
                            <td>{$shop['address']}</td>
                            <td><a href='shop.php?id={$shop['shop_id']}' class='btn btn-warning btn-sm'>View</a></td>
                        </tr>";
                    }
                    $html .= "</tbody></table></div>";
                    echo $html;
                } else {
                    echo "<p>No shops found.</p>";
                }
            ?>`;
            break;

        case 'services':
            content = `<?php
                $services_full = $conn->query("SELECT * FROM services");
                if ($services_full->num_rows > 0) {
                    $html = "<h4>All Services</h4>
                    <div class='table-responsive'>
                    <table id='servicesTable' class='table table-bordered text-light'>
                    <thead><tr><th>Service Name</th><th>Price</th></tr></thead><tbody>";
                    while ($service = $services_full->fetch_assoc()) {
                        $html .= "<tr>
                                    <td>{$service['service_name']}</td>
                                    <td>₱{$service['price']}</td>
                                  </tr>";
                    }
                    $html .= "</tbody></table></div>";
                    echo $html;
                } else {
                    echo "<p>No services available.</p>";
                }
            ?>`;
            break;

        case 'parts':
            content = `<?php
                $parts_full = $conn->query("SELECT * FROM parts");
                if ($parts_full->num_rows > 0) {
                    $html = "<h4>All Parts</h4>
                    <div class='table-responsive'>
                    <table id='partsTable' class='table table-bordered text-light align-middle'>
                    <thead><tr><th>Photo</th><th>Part Name</th><th>Price</th><th>Availability</th></tr></thead><tbody>";
                    while ($part = $parts_full->fetch_assoc()) {
                        $photoPath = !empty($part['photo']) ? 'uploads/parts/' . $part['photo'] : 'uploads/parts/default.png';
                        $html .= "<tr>
                                    <td><img src='{$photoPath}' class='part-thumb'></td>
                                    <td>{$part['part_name']}</td>
                                    <td>₱{$part['price']}</td>
                                    <td>{$part['availability']}</td>
                                  </tr>";
                    }
                    $html .= "</tbody></table></div>";
                    echo $html;
                } else {
                    echo "<p>No parts available.</p>";
                }
            ?>`;
            break;
    }

    document.getElementById('expandedContent').innerHTML = content;

    // ✅ Initialize DataTables dynamically
    setTimeout(() => {
        if (section === 'appointments') {
            $('#appointmentsTable').DataTable({ pageLength: 10, lengthChange: false, ordering: true, info: false });
        } 
        else if (section === 'shops') {
            $('#shopsTable').DataTable({ pageLength: 10, lengthChange: false, ordering: true, info: false });
        }
        else if (section === 'services') {
            $('#servicesTable').DataTable({ pageLength: 10, lengthChange: false, ordering: true, info: false });
        }
        else if (section === 'parts') {
            $('#partsTable').DataTable({ pageLength: 10, lengthChange: false, ordering: true, info: false });
        }
    }, 150);
}

function closeExpand() {
    document.getElementById('expandedView').classList.add('d-none');
    document.getElementById('dashboardGrid').classList.remove('d-none');
}
</script>

</body>
</html>
