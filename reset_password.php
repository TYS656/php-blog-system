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

$token = $_GET['token'] ?? '';
$email = User::validateResetToken($token);
if (!$email) {
    die("<div style='text-align:center;margin-top:100px;font-size:16px;'>重置链接无效或已过期，请<a href='forgot_password.php'>重新申请</a></div>");
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';
    
    if (strlen($newPwd) < 6) {
        $error = "密码至少6位";
    } elseif ($newPwd !== $confirmPwd) {
        $error = "两次密码不一致";
    } else {
        User::resetPasswordByEmail($email, $newPwd);
        User::deleteResetToken($token);
        $success = "密码重置成功，2秒后跳转到登录页";
        header("refresh:2;url=login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>重置密码</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;padding:80px 20px;}
.card{width:400px;margin:0 auto;background:#fff;padding:32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);}
h2{text-align:center;margin-bottom:20px;color:#2d3748;}
.msg{padding:10px;border-radius:6px;margin-bottom:16px;text-align:center;}
.msg.err{background:#fee;color:#dc2626;}
.msg.ok{background:#f0fdf4;color:#16a34a;}
.item{margin-bottom:16px;}
label{display:block;margin-bottom:6px;color:#4a5568;}
input{width:100%;padding:10px 12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;}
button{width:100%;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
button:hover{background:#1d4ed8;}
</style>
</head>
<body>
<div class="card">
    <h2>设置新密码</h2>
    <?php if($error): ?><div class="msg err"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <?php if($success): ?><div class="msg ok"><?=htmlspecialchars($success)?></div><?php endif; ?>

    <form method="post">
        <div class="item">
            <label>新密码</label>
            <input type="password" name="new_password" placeholder="至少6位" required>
        </div>
        <div class="item">
            <label>确认新密码</label>
            <input type="password" name="confirm_password" placeholder="再次输入" required>
        </div>
        <button type="submit">确认重置</button>
    </form>
</div>
</body>
</html>