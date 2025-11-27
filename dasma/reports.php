<?php
require 'config.php';
require_login();

// date filter
$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
  SELECT s.*, u.name as cashier
  FROM sales s
  LEFT JOIN users u ON u.id = s.user_id
  WHERE DATE(s.created_at) = ?
  ORDER BY s.created_at DESC
");
$stmt->execute([$date]);
$sales = $stmt->fetchAll();

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_ids'])) {
    $ids = $_POST['delete_ids'];
    if(!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtDelItems = $pdo->prepare("DELETE FROM sale_items WHERE sale_id IN ($placeholders)");
        $stmtDelItems->execute($ids);
        $stmtDelSales = $pdo->prepare("DELETE FROM sales WHERE id IN ($placeholders)");
        $stmtDelSales->execute($ids);
        $_SESSION['deleted'] = true;
        header("Location: reports.php?date=".urlencode($date));
        exit;
    }
}

// export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales-'.$date.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Invoice','Cashier','Total','Date']);
    foreach($sales as $s) { fputcsv($out, [$s['invoice_no'],$s['cashier'],$s['total'],$s['created_at']]); }
    fclose($out);
    exit;
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Reports - MinuteBurger</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    background-color: orange;
    font-family: sans-serif;
    margin:0;
}

/* SNOW BACKGROUND */
#snow-container {
    pointer-events: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 9999;
}

.snowflake {
    position: absolute;
    top: -10px;
    color: white;
    font-size: 1em;
    opacity: 0.8;
    animation: fall linear infinite;
}

@keyframes fall {
    0% { transform: translateY(0); }
    100% { transform: translateY(110vh); }
}

/* Navbar */
nav.navbar {
    position: sticky;
    top: 0;
    z-index: 999;
    background-color: #ff8000;
}

.nav-link {
    color:white !important;
    font-weight:bold;
}

.nav-link:hover {
    color:yellow !important;
}

.navbar-brand {
    font-weight:bold;
    color:white !important;
}

/* Logo wipe + sparkle */
.logo-wipe { position: relative; display: block; margin: 20px auto 15px auto; width: 150px; height: 150px; overflow: hidden; }
.logo-wipe img { width: 100%; height: 100%; }
.logo-wipe::after { content: ''; position: absolute; top: -100%; left: -100%; width: 200%; height: 200%; background: linear-gradient(45deg, rgba(255,255,255,0) 40%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0) 60%); animation: wipe 2s linear infinite; pointer-events: none; mask-image: url('minuteburgerlogo.png'); mask-repeat: no-repeat; mask-position: center; mask-size: contain; -webkit-mask-image: url('minuteburgerlogo.png'); -webkit-mask-repeat: no-repeat; -webkit-mask-position: center; -webkit-mask-size: contain; }
.logo-wipe .sparkle { position:absolute; width:5px; height:5px; border-radius:50%; background:rgba(255,255,255,0.2); opacity:0; animation:sparkle 1.5s infinite; }
@keyframes wipe { 0% { transform:translate(-100%,-100%); } 100% { transform:translate(100%,100%); } }
@keyframes sparkle { 0%,100%{opacity:0; transform:scale(0);} 50%{opacity:1; transform:scale(1);} }

/* Buttons */
.btn-primary, .btn-secondary, .btn-success {
    background-color: yellow !important;
    color: #000 !important;
    border: none !important;
    cursor: pointer;
}
.btn-danger {
    background-color: red !important;
    color: #fff !important;
    border: none !important;
    cursor: pointer;
}
</style>
</head>
<body>

<!-- SNOW CONTAINER -->
<div id="snow-container"></div>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php?page=home">MinuteBurger</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php?page=home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="dashboard.php?page=about">About Us</a></li>
            </ul>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-3">

  <div class="logo-wipe">
      <img src="minuteburgerlogo.png" alt="Logo">
      <span class="sparkle" style="top:10%; left:20%; animation-delay:0s;"></span>
      <span class="sparkle" style="top:50%; left:70%; animation-delay:0.5s;"></span>
      <span class="sparkle" style="top:80%; left:30%; animation-delay:1s;"></span>
  </div>

  <h4 class="mb-3">Sales Report (<?=htmlspecialchars($date)?>)</h4>

  <form method="get" class="row g-2 mt-2 align-items-center">
    <div class="col-auto">
      <input type="date" name="date" class="form-control" value="<?=htmlspecialchars($date)?>">
    </div>
    <div class="col-auto"><button class="btn btn-primary">Filter</button></div>
    <div class="col-auto"><button type="button" id="selectAllBtn" class="btn btn-primary">Select All</button></div>
    <div class="col-auto"><button type="button" id="deselectAllBtn" class="btn btn-primary">Deselect All</button></div>
    <div class="col-auto">
      <a class="btn btn-success" href="reports.php?date=<?=htmlspecialchars($date)?>&export=csv">Export CSV</a>
      <button type="submit" form="deleteForm" class="btn btn-danger ms-2">Delete</button>
    </div>
  </form>

  <form method="post" id="deleteForm">
  <table class="table table-bordered mt-3">
    <thead>
      <tr>
        <th></th>
        <th>Invoice</th>
        <th>Cashier</th>
        <th>Total</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($sales as $s): ?>
        <tr>
          <td><input type="checkbox" name="delete_ids[]" value="<?=$s['id']?>" class="delCheckbox"></td>
          <td><?=htmlspecialchars($s['invoice_no'])?></td>
          <td><?=htmlspecialchars($s['cashier'])?></td>
          <td>₱<?=number_format($s['total'],2)?></td>
          <td><?=htmlspecialchars($s['created_at'])?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </form>

</div>

<script>
// Snow effect
function createSnow() {
    const snow = document.createElement("div");
    snow.classList.add("snowflake");
    snow.innerHTML = "❄";
    snow.style.left = Math.random() * 100 + "vw";
    snow.style.fontSize = (Math.random() * 10 + 10) + "px";
    snow.style.opacity = Math.random();

    snow.style.animationDuration = (Math.random() * 3 + 2) + "s";

    document.getElementById("snow-container").appendChild(snow);

    setTimeout(() => snow.remove(), 5000);
}

setInterval(createSnow, 120);
</script>

<script>
// Select All
document.getElementById('selectAllBtn').addEventListener('click', function(){
    document.querySelectorAll('.delCheckbox').forEach(cb => cb.checked = true);
});

// Deselect All
document.getElementById('deselectAllBtn').addEventListener('click', function(){
    document.querySelectorAll('.delCheckbox').forEach(cb => cb.checked = false);
});
</script>

</body>
</html>
