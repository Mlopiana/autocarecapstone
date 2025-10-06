<?php
session_start();
require 'db_config.php';

// ✅ Retrieve all repair shop locations
$shops = [];
$result = $conn->query("SELECT shop_name, address, latitude, longitude, rating FROM repair_shops WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shops[] = $row;
    }
}
?>
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

    /* ✅ Larger and responsive map */
    #map {
      height: 80vh; /* fills most of the screen */
      width: 100%;
      border-radius: 10px;
      margin-top: 20px;
    }

    /* 📱 Responsive adjustments */
    @media (max-width: 768px) {
      #map {
        height: 60vh; /* slightly shorter on tablets */
      }
    }

    @media (max-width: 480px) {
      #map {
        height: 50vh; /* smaller height for phones */
      }
    }
  </style>
</head>
<body>

<div class="wrapper">

  <!-- ✅ Navbar -->
  <nav class="navbar navbar-expand-lg sticky-top" style="background: linear-gradient(90deg, #2c2c2c, #1a1a1a); border-bottom: 4px solid #ffcc00;">
    <div class="container-fluid">
      <a class="navbar-brand d-flex align-items-center text-warning fw-bold" href="index.php" style="font-family: 'Orbitron', sans-serif; letter-spacing: 2px;">
        <i class="bi bi-gear-fill me-2"></i> AutoCare
      </a>

      <button class="navbar-toggler text-warning border-warning" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="btn btn-warning btn-sm fw-bold" href="login.php">
              <i class="bi bi-box-arrow-in-right"></i> Login
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ✅ Content -->
  <div class="content">
    <div class="container mt-5">
      <h1 class="text-center">Welcome to AutoCare</h1>
      <p class="text-center">Find nearby vehicle repair shops in Bulan, Sorsogon.</p>
      <div id="map"></div>
    </div>
  </div>

  <!-- ✅ Footer -->
  <?php include 'includes/footer.php'; ?>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
  // Initialize map centered at Bulan
  var map = L.map('map').setView([12.6703, 123.8740], 14);

  // Add OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors'
  }).addTo(map);

  // ✅ Custom icons (from extracted)
  var shopIcon = L.icon({
      iconUrl: 'img/icons/repair.png', // wrench/garage icon
      iconSize: [28, 28],
      iconAnchor: [14, 28],
      popupAnchor: [0, -25]
  });

  var userIcon = L.icon({
      iconUrl: 'img/icons/car.png', // car icon
      iconSize: [30, 30],
      iconAnchor: [15, 30],
      popupAnchor: [0, -25]
  });

  // ✅ Load shop data from PHP
  var shops = <?php echo json_encode($shops); ?>;
  var markers = [];

  // Add shop markers
  shops.forEach(shop => {
      if (shop.latitude && shop.longitude) {
          var marker = L.marker([shop.latitude, shop.longitude], {icon: shopIcon})
              .bindPopup(`
                  <div style="text-align:center; max-width:180px;">
                      <h6 class="fw-bold">${shop.shop_name}</h6>
                      <p style="font-size:12px; margin:2px;">${shop.address}</p>
                      <p style="font-size:12px; color:gold;"><i class="bi bi-star-fill"></i> ${shop.rating}</p>
                  </div>
              `)
              .addTo(map);
          markers.push(marker);
      }
  });

  // ✅ Add user current location
  if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function(position) {
          var userLat = position.coords.latitude;
          var userLng = position.coords.longitude;

          var userMarker = L.marker([userLat, userLng], {icon: userIcon})
              .addTo(map)
              .bindPopup("<b>You are here</b>")
              .openPopup();

          markers.push(userMarker);

          // Adjust map to fit all markers
          var group = new L.featureGroup(markers);
          map.fitBounds(group.getBounds(), {padding: [50, 50]});
      }, function() {
          console.warn("User denied location access.");
      });
  } else {
      console.warn("Geolocation is not supported by this browser.");
  }
</script>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.
