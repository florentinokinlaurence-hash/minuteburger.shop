<?php
session_start();
require 'config.php';
require_login();
$user = $_SESSION['user'];

// Fetch items
$items = $pdo->query("SELECT id,name,unit_price,stock FROM inventory ORDER BY name")->fetchAll();

// Handle sale submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['items']) || !is_array($data['items'])) {
        echo json_encode(['status'=>'error','msg'=>'Invalid input']);
        exit;
    }

    $itemsPosted = $data['items'];
    $pdo->beginTransaction();
    try {
        $invoice = 'MB-'.time();
        $total = 0;

        foreach ($itemsPosted as $it) {
            $stmt = $pdo->prepare("SELECT id, unit_price, stock FROM inventory WHERE id=?");
            $stmt->execute([$it['id']]);
            $row = $stmt->fetch();
            if (!$row) throw new Exception('Item not found');
            if ($row['stock'] < intval($it['qty'])) throw new Exception("Insufficient stock for {$row['id']}");
            $total += $row['unit_price'] * intval($it['qty']);
        }

        $stmt = $pdo->prepare("INSERT INTO sales (invoice_no,user_id,total) VALUES (?,?,?)");
        $stmt->execute([$invoice,$user['id'],$total]);
        $saleId = $pdo->lastInsertId();

        $stmtIns = $pdo->prepare("INSERT INTO sale_items (sale_id,inventory_id,qty,price,subtotal) VALUES (?,?,?,?,?)");
        $stmtUpd = $pdo->prepare("UPDATE inventory SET stock = stock - ? WHERE id=?");

        foreach ($itemsPosted as $it) {
            $stmtS = $pdo->prepare("SELECT unit_price FROM inventory WHERE id=?");
            $stmtS->execute([$it['id']]);
            $r = $stmtS->fetch();
            $qty = intval($it['qty']);
            $price = $r['unit_price'];
            $subtotal = $qty * $price;
            $stmtIns->execute([$saleId,$it['id'],$qty,$price,$subtotal]);
            $stmtUpd->execute([$qty,$it['id']]);
        }

        $pdo->commit();
        echo json_encode(['status'=>'ok','invoice'=>$invoice,'total'=>$total]);
        exit;

    } catch(Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
        exit;
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>POS - MinuteBurger</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { margin:0; font-family:sans-serif; background:orange; }

/* ❄ SNOW EFFECT ❄ */
#snow-container {
    pointer-events: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 99999;
}
.snowflake {
    position: absolute;
    top: -10px;
    color: white;
    font-size: 1em;
    opacity: 0.9;
    animation: fall linear infinite;
}
@keyframes fall {
    0% { transform: translateY(0); }
    100% { transform: translateY(110vh); }
}

/* Navbar styling */
.navbar{position:relative; z-index:10; background-color:#ff8000;}
.navbar-brand{font-weight:bold;}
.nav-link{color:white!important;font-weight:bold; transition:color .3s;}
.nav-link:hover{color:yellow!important;}
.navbar .dropdown-menu{background-color:#ffbf00;}
.navbar .dropdown-item:hover{background-color:#ff9900;color:black;}

/* Logo wipe + sparkle */
.logo-wipe {
    position: relative;
    display: block;
    margin: 20px auto 15px auto;
    width: 150px;
    height: 150px;
    overflow: hidden;
}
.logo-wipe img { width: 100%; height: 100%; }
.logo-wipe::after {
    content: '';
    position: absolute;
    top: -100%;
    left: -100%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, rgba(255,255,255,0) 40%, rgba(255,255,255,0.15) 50%, rgba(255,255,255,0) 60%);
    animation: wipe 2s linear infinite;
    pointer-events: none;
    mask-image: url('minuteburgerlogo.png');
    mask-repeat: no-repeat;
    mask-position: center;
    mask-size: contain;
    -webkit-mask-image: url('minuteburgerlogo.png');
    -webkit-mask-repeat: no-repeat;
    -webkit-mask-position: center;
    -webkit-mask-size: contain;
}
.logo-wipe .sparkle {
    position: absolute;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    opacity: 0;
    animation: sparkle 1.5s infinite;
}
@keyframes wipe { 
    0% { transform: translate(-100%, -100%); } 
    100% { transform: translate(100%, 100%); } 
}
@keyframes sparkle {
    0%,100% { opacity: 0; transform: scale(0); }
    50% { opacity: 1; transform: scale(1); }
}

/* Item cards */
.item-card { cursor:pointer; position:relative; overflow:hidden; transition: transform 0.3s ease, box-shadow 0.3s ease; perspective:800px; }
.item-card:hover { transform: rotateX(10deg) rotateY(-10deg) scale(1.08); box-shadow:0 15px 25px rgba(0,0,0,0.5); }
.item-card img.item-img { position:absolute; top:5px; right:5px; width:40px; height:40px; object-fit:contain; pointer-events:none; z-index:2; }

.btn-primary, .btn-secondary { background-color: yellow !important; color:black !important; border:none !important; transition: transform 0.3s ease, box-shadow 0.3s ease; }
.animated-btn { cursor:pointer; perspective:800px; position:relative; }
.animated-btn:hover { transform: rotateX(10deg) rotateY(-10deg) scale(1.05); box-shadow:0 15px 25px rgba(255,255,0,0.7),0 0 20px rgba(255,255,0,0.5);}
</style>
</head>
<body>

<!-- ❄ SNOW CONTAINER ❄ -->
<div id="snow-container"></div>

<!-- Navbar with only Home and About Us -->
<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container-fluid">
<a class="navbar-brand" href="dashboard.php">MinuteBurger</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav me-auto mb-2 mb-lg-0">
    <li class="nav-item"><a class="nav-link" href="dashboard.php?page=home">Home</a></li>
    <li class="nav-item"><a class="nav-link" href="dashboard.php?page=about">About Us</a></li>
</ul>
<ul class="navbar-nav">
<li class="nav-item dropdown">
<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"><?=htmlspecialchars($user['name'])?></a>
<ul class="dropdown-menu dropdown-menu-end">
    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
</ul>
</li>
</ul>
</div>
</div>
</nav>

<div class="container mt-3">

  <!-- Top logo with wipe + sparkle -->
  <div class="logo-wipe">
      <img src="minuteburgerlogo.png" alt="Logo">
      <span class="sparkle" style="top:10%; left:20%; animation-delay:0s;"></span>
      <span class="sparkle" style="top:50%; left:70%; animation-delay:0.5s;"></span>
      <span class="sparkle" style="top:80%; left:30%; animation-delay:1s;"></span>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-2">
    <h4>Point of Sale</h4>
  </div>

  <!-- Items + Order Table -->
  <div class="row mt-3">
    <div class="col-md-7">
      <div class="row g-2">
        <?php foreach($items as $it):
            $img='';
            $name=strtolower(trim($it['name']));
            if(strpos($name,'bottled drink')!==false) $img='bottleddrink.png';
            else if(strpos($name,'cheesy burger')!==false) $img='cheesyburger.png';
            else if(strpos($name,'double minute burger')!==false) $img='doubleminuteburger.png';
            else if(strpos($name,'minute burger')!==false) $img='singleminuteburger.png';
            else if(strpos($name,'hotdog sandwich')!==false) $img='hotdogsandwich.png';
        ?>
        <div class="col-6">
          <div class="card p-2 item-card" 
               data-id="<?=$it['id']?>" 
               data-price="<?=$it['unit_price']?>" 
               data-stock="<?=intval($it['stock'])?>">
            <?php if($img): ?>
                <img src="<?=$img?>" class="item-img" alt="<?=$it['name']?>">
            <?php endif; ?>
            <div class="fw-bold"><?=$it['name']?></div>
            <div>₱<?=number_format($it['unit_price'],2)?> • Stock: <?=$it['stock']?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="col-md-5">
      <h6>Order</h6>
      <table class="table" id="orderTable">
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th><th></th></tr></thead>
        <tbody></tbody>
      </table>
      <div class="d-flex justify-content-between">
          <strong>Total:</strong>
          <h4 id="total">₱0.00</h4>
      </div>
      <button id="payBtn" class="btn btn-primary w-100 mt-2 animated-btn">Pay</button>
    </div>
  </div>
</div>

<script>
// ❄ SNOW GENERATOR ❄
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


// Order logic
let order=[];
function updateOrderTable(){
    const tbody=document.querySelector('#orderTable tbody'); 
    tbody.innerHTML=''; 
    let total=0;

    order.forEach((o,idx)=>{
        const subtotal=o.qty*o.price; 
        total+=subtotal;
        const tr=document.createElement('tr');
        tr.innerHTML=`<td>${o.name}</td>
                       <td>${o.qty}</td>
                       <td>₱${o.price.toFixed(2)}</td>
                       <td>₱${subtotal.toFixed(2)}</td>
                       <td><button data-idx="${idx}" class="btn btn-sm btn-danger remove">x</button></td>`;
        tbody.appendChild(tr);
    });

    document.getElementById('total').innerText='₱'+total.toFixed(2);

    document.querySelectorAll('.remove').forEach(btn=>{
        btn.onclick=()=>{
            order.splice(btn.dataset.idx,1);
            updateOrderTable();
        };
    });
}

document.querySelectorAll('.item-card').forEach(card=>{
    card.onclick=()=>{
        const id=card.dataset.id;
        const price=parseFloat(card.dataset.price);
        const stock=parseInt(card.dataset.stock)||0;
        const name=card.querySelector('.fw-bold').innerText;

        if(stock<=0){
            Swal.fire({icon:'warning',title:'Out of stock',text:`Cannot add ${name}`});
            return;
        }

        let existing=order.find(o=>o.id==id);
        if(existing){
            if(existing.qty+1>stock){
                Swal.fire({icon:'warning',title:'Insufficient stock',text:`Only ${stock} left`});
                return;
            }
            existing.qty++;
        } else {
            order.push({id,name,qty:1,price});
        }
        updateOrderTable();
    };
});

document.getElementById('payBtn').onclick=()=>{
    if(order.length===0){
        Swal.fire({icon:'warning',title:'No items',text:'Please add items before paying.'});
        return;
    }

    Swal.fire({
        title:"Do you want to buy these items?",
        icon:"question",
        showCancelButton:true,
        confirmButtonText:"Yes",
        cancelButtonText:"No"
    }).then(result=>{
        if(result.isConfirmed){
            fetch("",{
                method:"POST",
                headers:{"Content-Type":"application/json"},
                body:JSON.stringify({items:order.map(o=>({id:o.id,qty:o.qty}))})
            })
            .then(r=>r.json())
            .then(data=>{
                if(data.status==="ok"){
                    Swal.fire({
                        icon:"success",
                        title:"Purchase successful!",
                        text:"Invoice: "+data.invoice+"\nTotal: ₱"+parseFloat(data.total).toFixed(2)
                    }).then(()=>window.location.reload());
                } else {
                    Swal.fire({icon:"error",title:"Error",text:data.msg});
                }
            })
            .catch(()=>{
                Swal.fire({icon:"error",title:"Request Error",text:"Something went wrong."});
            });
        }
    });
};
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
