<?php
require 'config.php';
require_login();
$user = $_SESSION['user'];

// Add new item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_item') {
    $name = $_POST['name'] ?? '';
    $price = floatval($_POST['unit_price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    if ($name && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO inventory (name, unit_price, stock) VALUES (?, ?, ?)");
        $stmt->execute([$name, $price, $stock]);
        header('Location: inventory.php');
        exit;
    }
}

// Update stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stock') {
    $id = intval($_POST['id']);
    $stock = intval($_POST['stock']);
    $stmt = $pdo->prepare("UPDATE inventory SET stock=? WHERE id=?");
    $stmt->execute([$stock, $id]);
    $_SESSION['updated'] = true;
    header('Location: inventory.php');
    exit;
}

// Delete item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_item') {
    $delName = trim($_POST['delete_name'] ?? '');
    if ($delName) {
        $stmt = $pdo->prepare("SELECT id FROM inventory WHERE name=?");
        $stmt->execute([$delName]);
        $item = $stmt->fetch();
        if ($item) {
            $pdo->beginTransaction();
            try {
                $stmtDelSaleItems = $pdo->prepare("DELETE FROM sale_items WHERE inventory_id=?");
                $stmtDelInventory = $pdo->prepare("DELETE FROM inventory WHERE id=?");
                $stmtDelSaleItems->execute([$item['id']]);
                $stmtDelInventory->execute([$item['id']]);
                $pdo->commit();
                $_SESSION['deleted'] = $delName;
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['delete_error'] = $e->getMessage();
            }
        } else {
            $_SESSION['delete_error'] = "Item not found";
        }
        header('Location: inventory.php');
        exit;
    }
}

// Fetch inventory items
$items = $pdo->query("SELECT * FROM inventory ORDER BY name")->fetchAll();
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Inventory - MinuteBurger</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    margin:0;
    font-family:sans-serif;
    animation: smoothYellowOrange 18s infinite linear;
}

/* Background animation */
@keyframes smoothYellowOrange{
    0%{background:#fff200}
    10%{background:#ffe600}
    20%{background:#ffd200}
    30%{background:#ffbf00}
    40%{background:#ffad00}
    50%{background:#ff9900}
    60%{background:#ff8500}
    70%{background:#ff7000}
    80%{background:#ff5c00}
    90%{background:#ff6d00}
    95%{background:#ff9a00}
    100%{background:#fff200}
}

/* ❄️ SNOW EFFECT */
.snow {
    pointer-events:none;
    position:fixed;
    top:0; left:0;
    width:100%;
    height:100%;
    overflow:hidden;
    z-index:99999;
}
.snowflake {
    position:absolute;
    top:-10px;
    color:white;
    font-size:1em;
    opacity:0.9;
    user-select:none;
    animation:snowfall linear infinite;
}
@keyframes snowfall {
    0% { transform:translateY(0) rotate(0deg); }
    100% { transform:translateY(110vh) rotate(360deg); }
}

/* Navbar */
nav.navbar {
    position: sticky;
    top: 0;
    z-index: 999;
    background-color:#ff8000;
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

/* Logo Animation */
.logo-wipe {
    position:relative;
    display:block;
    margin:20px auto;
    width:150px;
    height:150px;
    overflow:hidden;
}
.logo-wipe img { width:100%; height:100%; }
.logo-wipe::after {
    content:'';
    position:absolute;
    top:-100%;
    left:-100%;
    width:200%;
    height:200%;
    background: linear-gradient(45deg, rgba(255,255,255,0) 40%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0) 60%);
    animation: wipe 2s linear infinite;
    pointer-events:none;
    mask-image:url('minuteburgerlogo.png');
    mask-repeat:no-repeat;
    mask-position:center;
    mask-size:contain;
    -webkit-mask-image:url('minuteburgerlogo.png');
    -webkit-mask-repeat:no-repeat;
    -webkit-mask-position:center;
    -webkit-mask-size:contain;
}
.logo-wipe .sparkle {
    position:absolute;
    width:5px;
    height:5px;
    border-radius:50%;
    background:rgba(255,255,255,0.2);
    opacity:0;
    animation:sparkle 1.5s infinite;
}
@keyframes wipe { 0%{transform:translate(-100%,-100%);} 100%{transform:translate(100%,100%);} }
@keyframes sparkle { 0%,100%{opacity:0; transform:scale(0);} 50%{opacity:1; transform:scale(1);} }

/* Buttons */
.btn-primary, .btn-secondary, .btn-danger {
    background-color: yellow !important;
    color: black !important;
    border:none !important;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.animated-btn:hover {
    transform: scale(1.05);
    box-shadow:0 15px 25px rgba(255,255,0,0.7),0 0 20px rgba(255,255,0,0.5);
}

.table th, .table td { vertical-align: middle; }
.table tbody tr td input[type=number] { width: 80px; }
</style>
</head>
<body>

<!-- ❄️ Snow Layer -->
<div class="snow">
<?php for ($i=0; $i<80; $i++): ?>
    <div class="snowflake" 
         style="
            left: <?= rand(0,100) ?>vw;
            animation-duration: <?= rand(8,18) ?>s;
            animation-delay: <?= rand(0,10) ?>s;
            font-size: <?= rand(8,18) ?>px;
         ">
        ❄
    </div>
<?php endfor; ?>
</div>

<!-- Navbar -->
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

<!-- LOGO -->
<div class="logo-wipe">
    <img src="minuteburgerlogo.png" alt="Logo">
    <span class="sparkle" style="top:10%; left:20%; animation-delay:0s;"></span>
    <span class="sparkle" style="top:50%; left:70%; animation-delay:0.5s;"></span>
    <span class="sparkle" style="top:80%; left:30%; animation-delay:1s;"></span>
</div>

<div class="container mt-3">
  <h4 class="mb-3">Inventory</h4>

  <div class="mb-3 d-flex gap-2">
    <button class="btn btn-primary animated-btn" data-bs-toggle="modal" data-bs-target="#addModal">Add Item</button>
    <button class="btn btn-danger animated-btn" data-bs-toggle="modal" data-bs-target="#deleteModal">Delete Item</button>
  </div>

  <table class="table table-bordered bg-white">
    <thead><tr><th>Name</th><th>Price</th><th>Stock</th><th>Action</th></tr></thead>
    <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?=htmlspecialchars($it['name'])?></td>
        <td>₱<?=number_format($it['unit_price'],2)?></td>
        <td><?=number_format($it['stock'])?></td>
        <td>
          <form method="post" class="d-inline updateForm">
            <input type="hidden" name="action" value="update_stock">
            <input type="hidden" name="id" value="<?=$it['id']?>">
            <input type="number" name="stock" value="<?=$it['stock']?>" min="0" required>
            <button type="button" class="btn btn-primary btn-sm animated-btn updateBtn">Update</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="add_item">
      <div class="modal-header"><h5 class="modal-title">Add Item</h5></div>
      <div class="modal-body">
        <div class="mb-3"><label>Name</label><input name="name" class="form-control" required></div>
        <div class="mb-3"><label>Unit Price</label><input name="unit_price" type="number" step="0.01" class="form-control" required></div>
        <div class="mb-3"><label>Stock</label><input name="stock" type="number" class="form-control" value="0"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary">Add</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete_item">
      <div class="modal-header"><h5 class="modal-title">Delete Item</h5></div>
      <div class="modal-body">
        <div class="mb-3">
          <label>Enter exact name of item to delete</label>
          <input name="delete_name" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger">Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// SweetAlert update confirmation
document.querySelectorAll('.updateBtn').forEach(btn => {
    btn.addEventListener('click', function() {
        let form = this.closest('.updateForm');
        Swal.fire({
            title: "Do you want to update?",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes",
            cancelButtonText: "No"
        }).then((result) => { if(result.isConfirmed){ form.submit(); } });
    });
});

// Success alerts
<?php if(isset($_SESSION['updated']) && $_SESSION['updated']): ?>
Swal.fire({ title:"Updated Successfully", icon:"success" });
<?php unset($_SESSION['updated']); endif; ?>

<?php if(isset($_SESSION['deleted'])): ?>
Swal.fire({ title:"Deleted <?=htmlspecialchars($_SESSION['deleted'])?>", icon:"success" });
<?php unset($_SESSION['deleted']); endif; ?>

<?php if(isset($_SESSION['delete_error'])): ?>
Swal.fire({ title:"Error", text:"<?=htmlspecialchars($_SESSION['delete_error'])?>", icon:"error" });
<?php unset($_SESSION['delete_error']); endif; ?>
</script>
</body>
</html>
