<?php
spl_autoload_register(function ($className) {
    $prefix = 'Blog\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    if (str_starts_with($className, $prefix)) {
        $relativeClass = substr($className, strlen($prefix));
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (file_exists($file)) require $file;
    }
});
session_start();
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: DENY");

use Blog\Models\User;

$error = '';
$fillUser = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = strtolower(trim($_POST['captcha'] ?? ''));
    $fillUser = $username;

    if (empty($username) || empty($password)) {
        $error = "用户名和密码不能为空";
    } elseif (empty($captcha) || $captcha !== ($_SESSION['captcha_code'] ?? '')) {
        $error = "验证码错误";
    } else {
        unset($_SESSION['captcha_code']);
        $user = User::findByUsername($username);
        if (!$user || !$user->verifyPassword($password)) {
            $error = "用户名或密码错误";
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['avatar'] = $user->avatar;
            header("Location: welcome.php", true, 302);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>账号登录</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;padding:60px 20px;}
.card{width:380px;margin:0 auto;background:#fff;padding:32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);}
h2{text-align:center;margin-bottom:24px;color:#2d3748;}
.err-box{background:#fee;color:#dc2626;padding:10px;border-radius:6px;margin-bottom:16px;text-align:center;}
.row{margin-bottom:16px;}
label{display:block;margin-bottom:6px;color:#4a5568;}
input{width:100%;padding:10px 12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;}
.captcha-row{display:flex;gap:10px;align-items:center;}
.captcha-row input{flex:1;}
.captcha-img{height:40px;border-radius:6px;cursor:pointer;border:1px solid #cbd5e0;}
button{width:100%;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
button:hover{background:#1d4ed8;}
.reg-link{text-align:center;margin-top:16px;font-size:14px;}
.reg-link a{color:#2563eb;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
    <h2>账号登录</h2>
    <?php if($error): ?>
        <div class="err-box"><?=htmlspecialchars($error,ENT_QUOTES)?></div>
    <?php endif; ?>
    <form method="post">
        <div class="row">
            <label>用户名</label>
            <input type="text" name="username" value="<?=htmlspecialchars($fillUser,ENT_QUOTES)?>">
        </div>
        <div class="row">
            <label>密码</label>
            <input type="password" name="password">
        </div>
        <div class="row">
            <label>验证码</label>
            <div class="captcha-row">
                <input type="text" name="captcha" placeholder="点击图片刷新" maxlength="4">
                <img class="captcha-img" src="captcha.php" onclick="this.src='captcha.php?'+Math.random()" alt="验证码">
            </div>
        </div>
        <button type="submit">登录</button>
    </form>
    <div class="reg-link">
        <a href="forgot_password.php">忘记密码？</a> | 
        <a href="register.php">没有账号？注册</a>
    </div>
</div>
</body>
</html>