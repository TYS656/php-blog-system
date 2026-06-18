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
if (empty($_SESSION['logged_in'])) {
    header("Location: login.php", true, 302);
    exit;
}
use Blog\Models\User;

$currentUser = User::findById($_SESSION['user_id']);
if (empty($_SESSION['csrf_pwd'])) {
    $_SESSION['csrf_pwd'] = bin2hex(random_bytes(32));
}
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPwd = $_POST['old_password'] ?? '';
    $newPwd = $_POST['new_password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_pwd'], $token)) {
        $error = "非法请求";
    } elseif (empty($oldPwd) || empty($newPwd)) {
        $error = "密码不能为空";
    } elseif (strlen($newPwd) < 6) {
        $error = "新密码至少6位";
    } elseif ($newPwd !== $confirmPwd) {
        $error = "两次新密码不一致";
    } elseif (!$currentUser->updatePassword($oldPwd, $newPwd)) {
        $error = "原密码错误";
    } else {
        $success = "密码修改成功，请重新登录";
        $_SESSION = [];
        session_destroy();
        // 发送跳转响应头
        header("refresh:2;url=login.php");
        // 单独渲染成功提示页面，不再加载下方表单
        echo <<<PAGE
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>修改登录密码</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;display:flex;justify-content:center;align-items:center;height:100vh;}
.tip{background:#f0fdf4;color:#16a34a;padding:20px 40px;border-radius:8px;font-size:18px;box-shadow:0 2px 12px rgba(0,0,0,0.07);}
</style>
</head>
<body>
    <div class="tip">{$success}，2秒后跳转到登录页</div>
</body>
</html>
PAGE;
        // 终止脚本，下方表单页面不再执行输出，彻底解决header报错
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>修改登录密码</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;padding:60px 20px;}
.card{width:420px;margin:0 auto;background:#fff;padding:36px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);}
h2{text-align:center;margin-bottom:24px;color:#2d3748;}
.msg{padding:10px;border-radius:6px;margin-bottom:16px;text-align:center;}
.msg.err{background:#fee;color:#dc2626;}
.msg.ok{background:#f0fdf4;color:#16a34a;}
.item{margin-bottom:16px;}
label{display:block;margin-bottom:6px;color:#4a5568;}
input{width:100%;padding:10px 12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;}
button{width:100%;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
button:hover{background:#1d4ed8;}
.back{display:block;text-align:center;margin-top:16px;color:#666;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
    <h2>修改登录密码</h2>
    <?php if($error): ?><div class="msg err"><?=htmlspecialchars($error)?></div><?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_pwd'])?>">
        <div class="item">
            <label>原密码</label>
            <input type="password" name="old_password" required>
        </div>
        <div class="item">
            <label>新密码</label>
            <input type="password" name="new_password" required>
        </div>
        <div class="item">
            <label>确认新密码</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit">确认修改</button>
    </form>
    <a class="back" href="welcome.php">返回个人中心</a>
</div>
</body>
</html>