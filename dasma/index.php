<?php
session_start();
require 'config.php';
$err = '';
$successMsg = '';

// POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // LOGIN
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if ($email && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            if ($u && hash('sha256', $password) === $u['password']) {
                $_SESSION['user'] = [
                    'id' => $u['id'],
                    'name' => $u['name'],
                    'email' => $u['email'],
                    'role' => $u['role']
                ];
                $_SESSION['welcome'] = true;
                header('Location: dashboard.php');
                exit;
            } else $err = "Invalid credentials.";
        } else $err = "Enter email and password.";
    }

    // RESET PASSWORD
    if ($action === 'reset_password') {
        $email = $_POST['email'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if ($email && $new && $confirm) {
            if ($new === $confirm) {
                $stmt = $pdo->prepare("UPDATE users SET password=? WHERE email=?");
                $stmt->execute([hash('sha256',$new), $email]);
                $successMsg = "Password changed successfully!";
            } else {
                $err = "Passwords do not match!";
            }
        } else $err = "All fields are required!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MinuteBurger Login / Reset</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body {
    background: url('web.jpg') no-repeat center center fixed;
    background-size: cover;
    height: 100vh;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
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

/* Dark overlay */
body::before {
    content:"";
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.35);
    backdrop-filter:blur(1px);
    z-index:0;
}

.login-container {
    position: relative;
    z-index:1;
    width: 100%;
    max-width: 500px;
    display: flex;
    flex-direction: column;
    background: rgba(255, 255, 255, 0.15); 
    backdrop-filter: blur(10px);
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.5);
    padding: 40px;
    color: #fff;
}

.logo-btn {background:none;border:none;padding:0;margin-bottom:10px;width:100%;display:flex;justify-content:center;cursor:pointer;}
.logo-btn img {width:160px;height:auto;border-radius:8px;animation: logoGlow 3s infinite;}
@keyframes logoGlow {0%,100%{filter: drop-shadow(0 0 5px #fff);}50%{filter: drop-shadow(0 0 20px #ff0);}}

/* Inputs */
.input-container{position:relative;margin-bottom:0.5in;}
input.form-control {
    padding-left:10px;
    padding-right:40px;
    background: rgba(255,255,255,0.25); 
    border: none;
    color: #000;
}
input.form-control::placeholder { color: #333; }

.input-right-icon{position:absolute;top:50%;right:10px;transform:translateY(-50%);width:24px;height:24px;pointer-events:none;}

.toggle-password {
    position:absolute;top:50%;right:10px;
    transform:translateY(-50%);
    border:none;background:none;cursor:pointer;
    z-index:2;width:24px;height:24px;
    display:flex;align-items:center;justify-content:center;
}

.back-button {background-color: rgba(255, 215, 0, 0.6); color:#000; font-weight:bold; margin-bottom:15px;}
.forgot-password{cursor:pointer;color:blue;text-decoration:underline;margin-top:5px;}

/* Gleam button */
.btn-gleam {
    background: linear-gradient(90deg, #ff9900, #ff6600, #ffcc66, #ff9900);
    background-size: 400%;
    color: #000;
    font-weight: bold;
    border: none;
    transition: transform 0.3s ease;
    animation: gleam 4s linear infinite;
}
.btn-gleam:hover { transform: scale(1.05); }

@keyframes gleam {
    0% {background-position:0%;}
    50% {background-position:100%;}
    100% {background-position:0%;}
}

.alert {background: rgba(255,255,255,0.3); color:#000; border:none;}
</style>
</head>
<body>

<!-- SNOW -->
<div id="snow-container"></div>

<div class="login-container">
    <button class="logo-btn"><img src="minuteburgerlogo.png" alt="Logo"></button>
    <h4 class="text-center mb-3">Minute Burger System</h4>

    <?php if($err): ?><div class="alert alert-danger"><?=htmlspecialchars($err)?></div><?php endif; ?>
    <?php if($successMsg): ?><div class="alert alert-success"><?=htmlspecialchars($successMsg)?></div><?php endif; ?>

    <form method="post" id="loginFormInner">
        <input type="hidden" name="action" value="login">
        <div class="mb-3 input-container">
            <label>Email or Phone</label>
            <input name="email" type="text" class="form-control" required>
            <img src="email.png" class="input-right-icon">
        </div>

        <div class="mb-3 input-container">
            <label>Password</label>
            <input id="loginPassword" name="password" type="password" class="form-control" required>
            <button type="button" class="toggle-password" onclick="togglePassword('loginPassword')">👁</button>
        </div>

        <div class="forgot-password" onclick="sendVerification()">Forgot Password?</div>
        <button type="submit" class="btn btn-gleam w-100 mt-2">Login</button>
    </form>

    <form method="post" id="resetPassForm" style="display:none;">
        <input type="hidden" name="action" value="reset_password">

        <div class="mb-3 input-container" id="newPasswordFields" style="display:none;">
            <label>New Password</label>
            <input name="new_password" type="password" class="form-control" required>
            <button type="button" class="toggle-password" onclick="togglePasswordField(this)">👁</button>
        </div>

        <div class="mb-3 input-container" id="confirmPasswordFields" style="display:none;">
            <label>Confirm New Password</label>
            <input name="confirm_password" type="password" class="form-control" required>
            <button type="button" class="toggle-password" onclick="togglePasswordField(this)">👁</button>
        </div>

        <button type="submit" class="btn btn-gleam w-100 mt-2" id="changePasswordBtn" style="display:none;">Change Password</button>

        <button type="button" class="btn back-button mt-2" onclick="showLogin()">Back</button>
        <input type="hidden" name="email" value="">
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

// Password toggle
function togglePassword(id){
    const pwd = document.getElementById(id);
    pwd.type = (pwd.type==='password')?'text':'password';
}
function togglePasswordField(btn){
    const input = btn.previousElementSibling;
    input.type = (input.type==='password')?'text':'password';
}

function sendVerification(){
    const emailInput = document.querySelector('#loginFormInner [name=email]').value;
    if(!emailInput){ alert("Enter email or phone first."); return; }

    const code = Math.floor(100000 + Math.random()*900000);
    alert(`Verification code sent to ${emailInput}: ${code}`);

    const userCode = prompt("Enter the 6-digit verification code:");
    if(userCode == code){
        Swal.fire('Verified','You can now reset your password.','success');
        document.getElementById('loginFormInner').style.display='none';
        document.getElementById('resetPassForm').style.display='block';
        document.querySelector('#resetPassForm [name=email]').value = emailInput;
        document.getElementById('newPasswordFields').style.display='block';
        document.getElementById('confirmPasswordFields').style.display='block';
        document.getElementById('changePasswordBtn').style.display='block';
    } else {
        Swal.fire('Wrong Code','The code you entered is incorrect.','error');
    }
}

function showLogin(){
    document.getElementById('loginFormInner').style.display='block';
    document.getElementById('resetPassForm').style.display='none';
    document.getElementById('newPasswordFields').style.display='none';
    document.getElementById('confirmPasswordFields').style.display='none';
    document.getElementById('changePasswordBtn').style.display='none';
}
</script>

</body>
</html>
