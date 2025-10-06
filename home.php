<?php session_start(); ?> 
<!DOCTYPE html>
<html>
<head>     
  <title>AutoCare Home</title>     
  <meta name="viewport" content="width=device-width, initial-scale=1">     
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">      
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />     
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500&display=swap" rel="stylesheet">
  
  <style>         
    html, body {
      height: 100%;
      margin: 0;
    }

    .wrapper {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    .content {
      flex: 1;
    }

    #map {             
      height: calc(100vh - 250px); /* taller map */
      width: 100%;             
      border-radius: 10px;             
      margin-top: 20px;         
    }     
  </style> 
</head> 
<body>  

<div class="wrapper">

  <!-- ✅ Navbar -->
  <nav class="navbar navbar-expand-lg sticky-top" style="background: linear-gradient(90deg, #2c2c2c, #1a1a1a); border-bottom: 4px solid #ffcc00;">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center text-warning fw-bold" href="home.php" style="font-family: 'Orbitron', sans-serif; letter-spacing: 2px;">
        <i class="bi bi-gear-fill me-2"></i> AutoCare
      </a>
      <button class="navbar-toggler text-warning border-warning" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link text-light fw-semibold" href="customer_dashboard.php#top"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
          <li class="nav-item"><a class="nav-link text-light fw-semibold" href="customer_dashboard.php#appointmentsSection"><i class="bi bi-calendar-check me-1"></i> Appointments</a></li>
          <li class="nav-item"><a class="nav-link text-light fw-semibold" href="customer_dashboard.php#shopsSection"><i class="bi bi-geo-alt-fill me-1"></i> Shops</a></li>
          <li class="nav-item"><a class="nav-link text-light fw-semibold" href="customer_dashboard.php#servicesSection"><i class="bi bi-tools me-1"></i> Services</a></li>
          <li class="nav-item"><a class="nav-link text-light fw-semibold" href="customer_dashboard.php#partsSection"><i class="bi bi-nut-fill me-1"></i> Parts</a></li>
        </ul>

        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <button type="button" class="btn btn-sm btn-outline-warning fw-bold me-3" data-bs-toggle="modal" data-bs-target="#userModal">
              <i class="bi bi-person-circle me-1"></i> <?php echo $_SESSION['name'] ?? 'Guest'; ?>
            </button>
          </li>
          <li class="nav-item">
            <a class="btn btn-warning btn-sm fw-bold" href="index.php">
              <i class="bi bi-box-arrow-right"></i> Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ✅ User Info Modal -->
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

  <!-- ✅ Page Content -->
  <div class="content">
    <div class="container mt-5">     
      <h1 class="text-center">Welcome to AutoCare</h1>     
      <p class="text-center">Find nearby vehicle repair shops in Bulan, Sorsogon.</p>      
      <div id="map"></div> 
    </div>  
  </div>

  <!-- ✅ Footer -->
  <?php include 'includes/footer.php'; ?>  

</div> <!-- end wrapper -->

<!-- Leaflet JS --> 
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script> 
<script>     
  var map = L.map('map');
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);      
  var markers = L.featureGroup().addTo(map);

  // Define icons
  var carIcon = L.icon({
    iconUrl: "img/icons/car.png",
    iconSize: [28, 28],
    iconAnchor: [14, 28],
    popupAnchor: [0, -25]
  });

  var garageIcon = L.icon({
    iconUrl: "img/icons/repair.png",
    iconSize: [26, 26],
    iconAnchor: [13, 26],
    popupAnchor: [0, -20]
  });

  // ✅ Add shop markers with status + button
  <?php
  require 'db_config.php';
  date_default_timezone_set('Asia/Manila');

  $shops = $conn->query("SELECT shop_id, shop_name, address, latitude, longitude, status FROM repair_shops WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
  while ($row = $shops->fetch_assoc()) {
      $id = $row['shop_id'];
      $name = addslashes($row['shop_name']);
      $address = addslashes($row['address']);
      $lat = $row['latitude'];
      $lng = $row['longitude'];
      $status = strtolower($row['status']);

      // ✅ Choose color badge
      $badgeColor = ($status === 'open') ? '#198754' : '#dc3545'; // green or red
      $label = ucfirst($status);

      echo "L.marker([$lat, $lng], {icon: garageIcon}).addTo(markers)
        .bindPopup(`
          <div style='text-align:center; max-width:180px;'>
            <h6 style='margin:0; font-size:14px;'>$name</h6>
            <p style='margin:5px 0; font-size:10px; color:#555;'>$address</p>
            <span style='display:inline-block; background:$badgeColor; color:white; padding:2px 8px; border-radius:10px; font-size:10px;'>$label</span><br>
            <a href='shop_page.php?id=$id' class='btn btn-sm btn-warning text-dark fw-bold mt-2'>View Shop</a>
          </div>
        `);\n";
  }
  ?>

  // ✅ Adjust map view
  if (markers.getLayers().length > 0) {
    map.fitBounds(markers.getBounds());
  } else {
    map.setView([12.6703, 123.8740], 14); 
  }

  // ✅ User location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function (position) {
        var userLat = position.coords.latitude;
        var userLng = position.coords.longitude;

        var userMarker = L.marker([userLat, userLng], { icon: carIcon })
          .addTo(map)
          .bindPopup("<b>Your Vehicle</b>")
          .openPopup();

        markers.addLayer(userMarker);
        map.fitBounds(markers.getBounds(), { padding: [50, 50] });
      },
      function (error) {
        console.warn("Geolocation error:", error.message);
      }
    );
  }

  // ✅ Resize icons dynamically
  function resizeIcons() {
    var zoom = map.getZoom();
    var size = Math.max(14, zoom * 2);
    carIcon.options.iconSize = [size, size];
    garageIcon.options.iconSize = [size - 2, size - 2];

    map.eachLayer(function(layer) {
      if (layer instanceof L.Marker && layer.setIcon) {
        if (layer.options.icon === carIcon) {
          layer.setIcon(carIcon);
        } else {
          layer.setIcon(garageIcon);
        }
      }
    });
  }
  map.on("zoomend", resizeIcons);
  resizeIcons();
</script>  

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body> 
</html>
