<?php
spl_autoload_register(function ($className) {
    $prefix = 'Blog\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;
    if (str_starts_with($className, $prefix)) {
        $relativeClass = substr($className, strlen($prefix));
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (file_exists($file)) require_once $file;
    }
});
session_start();
use Blog\Models\User;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "请输入正确的邮箱";
    } elseif (!User::findByEmail($email)) {
        $success = "若该邮箱已注册，重置链接已发送至您的邮箱，30分钟内有效";
    } else {
        $token = User::createResetToken($email);
        $resetUrl = "reset_password.php?token=$token";
        $success = "重置链接已生成：<a href='$resetUrl'>点击立即重置</a>（生产环境会发送到邮箱）";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>忘记密码</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;padding:80px 20px;}
.card{width:400px;margin:0 auto;background:#fff;padding:32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);}
h2{text-align:center;margin-bottom:20px;color:#2d3748;}
.msg{padding:10px;border-radius:6px;margin-bottom:16px;text-align:center;font-size:14px;}
.msg.err{background:#fee;color:#dc2626;}
.msg.ok{background:#f0fdf4;color:#16a34a;}
.item{margin-bottom:16px;}
label{display:block;margin-bottom:6px;color:#4a5568;}
input{width:100%;padding:10px 12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;}
button{width:100%;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
.link{text-align:center;margin-top:16px;font-size:14px;}
.link a{color:#2563eb;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
    <h2>找回密码</h2>
    <?php if($error): ?><div class="msg err"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <?php if($success): ?><div class="msg ok"><?=$success?></div><?php endif; ?>

    <form method="post">
        <div class="item">
            <label>注册邮箱</label>
            <input type="email" name="email" placeholder="输入注册时的邮箱" required>
        </div>
        <button type="submit">发送重置链接</button>
    </form>
    <div class="link"><a href="login.php">返回登录</a></div>
</div>
</body>
</html>