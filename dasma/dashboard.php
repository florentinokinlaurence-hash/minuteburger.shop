<?php
session_start();
require 'config.php';
require_login();

// User info
$user = $_SESSION['user'] ?? [
    'id' => 1,
    'name' => 'Admin',
    'email' => 'admin@minuteburger.com',
    'contact' => '(02) 8776 7740',
    'role' => 'Administrator'
];
$user['contact'] = $user['contact'] ?? '';
$user['email'] = $user['email'] ?? '';
$user['role'] = $user['role'] ?? '';

// Dashboard counts
$invCount = $pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
$stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) FROM sales WHERE DATE(created_at)=CURDATE()");
$stmt->execute();
$todaySales = $stmt->fetchColumn();
$totalSales = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();

// Welcome popup
$showWelcome = false;
if (!empty($_SESSION['welcome'])) {
    $showWelcome = true;
    unset($_SESSION['welcome']);
}

// Determine page
$page = $_GET['page'] ?? 'home';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>MinuteBurger Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<style>
body {
    margin:0;
    font-family:sans-serif;
    animation: smoothYellowOrange 18s infinite linear;
    overflow-x: hidden;
    position: relative;
}
/* Background gradient */
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
/* Christmas lights */
.christmas-lights { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
.light { position: absolute; width: 6px; height: 6px; border-radius: 50%; box-shadow: 0 0 5px yellow; animation: twinkle 2s infinite alternate; }
@keyframes twinkle { 0% { opacity:0.2; transform: scale(1); } 50% { opacity:1; transform: scale(1.4); } 100% { opacity:0.2; transform: scale(1); } }
/* Big Moving Christmas Tree */
.christmas-tree { position: fixed; bottom: 0; font-size: 200px; opacity: 0.8; z-index: 0; pointer-events: none; animation: swayTree 4s ease-in-out infinite alternate; }
@keyframes swayTree { 0% { transform: translateY(0) rotate(-5deg); } 50% { transform: translateY(-30px) rotate(5deg); } 100% { transform: translateY(0) rotate(-5deg); } }
/* Navbar */
nav.navbar { position: sticky; top: 0; z-index: 999; background-color:#ff8000; }
.nav-link { position: relative; z-index: 1; padding: 6px 14px; border-radius: 20px; color:white !important; font-weight:bold; transition: 0.3s ease; }
.nav-link::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 20px; background: rgba(255,140,0,0); box-shadow: 0 0 0px rgba(255,140,0,0); z-index: -1; transition: 0.3s ease; }
.nav-link:hover { color: #fff !important; }
.nav-link:hover::after { background: #ff8c00; box-shadow: 0 0 12px 5px rgba(255,140,0,0.8);}
.nav-link.active::after { background: #ff9a00; box-shadow: 0 0 12px 5px rgba(255,140,0,0.8);}
.navbar-brand { font-weight:bold; color:white !important; }
/* Logo Animation */
.logo-wipe { position:relative; display:block; margin:30px auto 15px auto; width:150px; height:150px; overflow:hidden; }
.logo-wipe img { width:100%; height:100%; }
.logo-wipe::after { content:''; position:absolute; top:-100%; left:-100%; width:200%; height:200%; background:linear-gradient(45deg, rgba(255,255,255,0) 40%, rgba(255,255,255,0.20) 50%, rgba(255,255,255,0) 60%); animation:wipe 2s linear infinite; pointer-events:none; mask-image:url('minuteburgerlogo.png'); -webkit-mask-image:url('minuteburgerlogo.png'); }
@keyframes wipe { 0% { transform:translate(-100%,-100%); } 100% { transform:translate(100%,100%); } }
/* Dashboard cards */
.dashboard-row{ display:flex; justify-content:center; gap:30px; flex-wrap:wrap; margin-top:20px; }
.card-container { text-align:center; perspective:800px; position: relative; }
.card{ width:250px; border-radius:15px; background: linear-gradient(45deg,#fff200,#ff9900); color:black; position:relative; transition: transform 0.3s ease, box-shadow 0.3s ease; padding: 20px; overflow: hidden; z-index: 1; }
.card::after { content: ''; position: absolute; top:0; left:0; width:100%; height:100%; border-radius:15px; background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.25) 50%, rgba(255,255,255,0.05) 100%); opacity: 0; pointer-events: none; transition: opacity 2s ease; z-index: 2; }
.card::before { content:''; position:absolute; top:-2px; left:-2px; right:-2px; bottom:-2px; background: linear-gradient(45deg, red, orange, yellow, red); border-radius: 17px; z-index:-1; filter: blur(6px); opacity:0.8; background-size: 400% 400%; animation: borderGlow 3s linear infinite; }
@keyframes borderGlow {0% { background-position:0% 50%; } 50% { background-position:100% 50%; } 100% { background-position:0% 50%; }}
.card:hover { transform: scale(1.05) rotateX(5deg) rotateY(5deg); box-shadow: 0 0 40px 10px rgba(255,200,0,0.6), 0 0 60px 15px rgba(255,140,0,0.4) inset; }
.card:hover::after { opacity: 1; }
.btn-card{ color:black; border:none; width:100%; padding:12px 0; font-weight:bold; margin-top:10px; font-size:16px; background:linear-gradient(45deg,#fff200,#ff9900); transition: transform 0.3s ease, box-shadow 0.3s ease; }
.btn-card:hover { transform: scale(1.05); box-shadow: 0 0 20px 5px rgba(255,255,0,0.5); }
.about-content { margin: 1in; font-size: 16px; line-height: 1.6; }
/* Snow + Ornaments */
.snowflake { position: fixed; top: -10px; color: white; font-size: 1em; animation: fall linear infinite; opacity: 0.8; z-index: 9999; pointer-events: none; }
@keyframes fall { 0% { transform: translateY(-10px); opacity:1; } 100% { transform: translateY(110vh); opacity:0.6; } }
.ornament { font-size:1.2em; color:red; position: fixed; top:-20px; animation: fallOrnament linear infinite; z-index:9999; }
@keyframes fallOrnament { 0% { transform: translateY(-20px) rotate(0deg);} 100% { transform: translateY(110vh) rotate(360deg); } }
</style>
</head>
<body>
<!-- Christmas background elements -->
<div class="christmas-lights"></div>
<div class="christmas-tree" style="left:50%;">🎄</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Snowflakes
    const snowCount = 60;
    for (let i = 0; i < snowCount; i++) {
        let snow = document.createElement("div");
        snow.classList.add("snowflake");
        snow.innerHTML = "❄";
        snow.style.left = Math.random() * 100 + "vw";
        snow.style.fontSize = (Math.random() * 12 + 8) + "px";
        snow.style.animationDuration = (Math.random() * 5 + 4) + "s";
        snow.style.animationDelay = (Math.random() * 5) + "s";
        document.body.appendChild(snow);
    }

    // Ornaments
    const ornamentCount = 15;
    const colors = ["red","green","gold"];
    for(let i=0;i<ornamentCount;i++){
        let orb=document.createElement("div");
        orb.classList.add("ornament");
        orb.innerHTML = "🎄";
        orb.style.left = Math.random()*100+"vw";
        orb.style.fontSize = (Math.random()*18+12)+"px";
        orb.style.color = colors[Math.floor(Math.random()*colors.length)];
        orb.style.animationDuration = (Math.random()*6+5)+"s";
        orb.style.animationDelay = (Math.random()*6)+"s";
        document.body.appendChild(orb);
    }

    // Christmas lights
    const lightsContainer = document.querySelector('.christmas-lights');
    const lightCount = 40;
    for(let i=0;i<lightCount;i++){
        let light = document.createElement('div');
        light.classList.add('light');
        light.style.top = Math.random()*100 + "vh";
        light.style.left = Math.random()*100 + "vw";
        light.style.background = ["red","green","yellow","blue"][Math.floor(Math.random()*4)];
        light.style.animationDuration = (Math.random()*2+1)+"s";
        lightsContainer.appendChild(light);
    }
});
</script>

<nav class="navbar navbar-expand-lg navbar-dark">
<div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php?page=home">MinuteBurger</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
            <li class="nav-item">
                <a class="nav-link <?=($page=='home')?'active':''?>" href="dashboard.php?page=home">Home</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?=($page=='about')?'active':''?>" href="dashboard.php?page=about">About Us</a>
            </li>
        </ul>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</div>
</nav>

<div class="logo-wipe">
    <img src="minuteburgerlogo.png">
</div>

<div class="container mt-4">
<?php if ($page=='home'): ?>
    <div class="dashboard-row">
        <div class="card-container">
            <div class="card p-3 shadow-sm">
                <h6>Today's Sales</h6>
                <h3>₱<?=number_format($todaySales,2)?></h3>
            </div>
            <a href="pos.php" class="btn-card">Open POS</a>
        </div>
        <div class="card-container">
            <div class="card p-3 shadow-sm">
                <h6>Inventory Items</h6>
                <h3><?=number_format($invCount)?></h3>
            </div>
            <a href="inventory.php" class="btn-card">Inventory</a>
        </div>
        <div class="card-container">
            <div class="card p-3 shadow-sm">
                <h6>Total Transactions</h6>
                <h3><?=number_format($totalSales)?></h3>
            </div>
            <a href="reports.php" class="btn-card">Reports</a>
        </div>
    </div>
<?php elseif ($page=='about'): ?>
    <div class="about-content">
        <h2>About Minute Burger - Bansalan, Davao del Sur</h2>
        <p>Minute Burger is a well-known Filipino burger franchise that has grown popular for offering affordable yet flavorful burgers suited to local tastes. Founded in 1982, it began during a time when Western-style fast food was becoming more common in the Philippines. The brand started small, using mobile snack carts stationed in accessible areas such as gasoline stations, which helped introduce the brand to everyday commuters and workers.</p>
        <p>The company behind Minute Burger is Leslie Corporation, a major Philippine food manufacturer known for products like Clover Chips. With the backbone of a strong manufacturing company, Minute Burger was able to develop a stable supply chain and consistent product quality. This partnership allowed the brand to expand steadily across the country over several decades.</p>
        <p>One unique characteristic of Minute Burger is its multi-unit franchise model. Instead of offering single-store franchises by default, franchisees are encouraged—or sometimes required—to open multiple stores within a specific territory. This strategy helps ensure strong market presence, prevents franchisees from competing with each other, and increases profitability through better territory management.</p>
        <p>Minute Burger is considered one of the more affordable franchise opportunities in the fast-food sector. The estimated cost to open a store is much lower compared to large international brands, making it attractive to first-time entrepreneurs. The package typically includes the franchise fee, equipment, initial stock, and various operational tools needed to begin business operations smoothly.</p>
        <p>The company provides franchisees with strong operational support through logistics, training, and supply chain management. They operate commissaries and warehouses that deliver burger patties, buns, and other essentials efficiently. Staff training programs include hands-on preparation, customer service, and real-world simulations to ensure consistent quality across all stores.</p>
        <p>Minute Burger’s menu focuses on its core strengths: value sandwiches and premium sandwiches. Their signature products appeal to budget-conscious Filipino customers while still offering good flavor and portion size. They also include drinks and seasonal items, maintaining a menu that balances affordability with variety.</p>
        <p>Food quality and safety are important parts of their brand identity. Their ingredients follow national standards, and the company highlights their compliance with food safety certifications. This helps build trust among customers seeking safe and consistent meals, especially in the quick-service food industry.</p>
        <p>Today, the brand has expanded to hundreds of stores nationwide, making it a familiar roadside presence in many towns and cities. The combination of affordable prices, recognizable branding, and strategic franchise expansion has contributed to its wide reach and strong customer base.</p>
        <p>Minute Burger also invests in technology to improve operations. Systems are in place to monitor inventory levels, manage store orders, and streamline product deliveries. Their logistics process allows franchisees to restock efficiently, helping stores avoid shortages and maintain smooth service, especially during high-demand periods.</p>
        <p>Despite its success, the brand still faces challenges such as market competition, varying store profitability depending on location, and maintaining consistent service across all outlets. However, its long history, strong corporate backing, and loyal customer base continue to drive its relevance in the Filipino fast-food landscape. Minute Burger remains a popular choice for both customers looking for affordable meals and entrepreneurs seeking franchise opportunities.</p>
    </div>
<?php endif; ?>

<?php if ($showWelcome): ?>
<script>
Swal.fire({
    title:'Welcome, <?=htmlspecialchars($user["name"])?>!',
    width:'50%',
    padding:'2em',
    icon:'success',
    confirmButtonText:'Proceed',
    allowOutsideClick:false,
    allowEscapeKey:false
}).then(()=>{
    const duration = 4000;
    const end = Date.now() + duration;
    (function frame(){
        confetti({particleCount:5, angle:60, spread:55, origin:{x:0}});
        confetti({particleCount:5, angle:120, spread:55, origin:{x:1}});
        if(Date.now() < end) requestAnimationFrame(frame);
    })();
});
</script>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
