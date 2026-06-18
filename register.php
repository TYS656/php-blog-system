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

if (empty($_SESSION['csrf_reg'])) {
    $_SESSION['csrf_reg'] = bin2hex(random_bytes(32));
}
$errors = [];
$fill = ['username' => '', 'email' => ''];
$uploadDir = __DIR__ . '/uploads/avatar/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
$avatarPath = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_reg'], $_POST['csrf_token'] ?? '')) {
        $errors['global'] = "非法提交请求";
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $fill['username'] = $username;
    $fill['email'] = $email;

    if (empty($username)) $errors['username'] = "用户名不能为空";
    elseif (mb_strlen($username) < 3) $errors['username'] = "用户名至少3字符";

    if (empty($password)) $errors['password'] = "密码不能为空";
    elseif (strlen($password) < 6) $errors['password'] = "密码最少6位";

    if ($password !== $password2) $errors['password2'] = "两次密码不一致";

    if (empty($email)) $errors['email'] = "邮箱不能为空";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "邮箱格式错误";

    // 头像上传
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['avatar'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['avatar'] = "文件上传失败";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (!in_array($mime, ['image/jpeg','image/png'])) {
                $errors['avatar'] = "仅支持jpg/png";
            } elseif ($file['size'] > 1048576) {
                $errors['avatar'] = "头像不超过1MB";
            } else {
                $ext = $mime === 'image/png' ? 'png' : 'jpg';
                $name = md5(uniqid(true).$username).".".$ext;
                move_uploaded_file($file['tmp_name'], $uploadDir.$name);
                $avatarPath = "uploads/avatar/".$name;
            }
        }
    }

    if (empty($errors)) {
        if (User::isExists($username, $email)) {
            $errors['global'] = "用户名或邮箱已被注册";
        } else {
            $uid = User::create($username, $password, $email, $avatarPath);
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $uid;
            $_SESSION['username'] = $username;
            $_SESSION['avatar'] = $avatarPath;
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
<title>用户注册</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;padding:60px 20px;}
.card{width:420px;margin:0 auto;background:#fff;padding:32px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);}
h2{text-align:center;margin-bottom:24px;color:#2d3748;}
.global-err{background:#fee;color:#dc2626;padding:10px;border-radius:6px;margin-bottom:16px;text-align:center;}
.item{margin-bottom:16px;}
label{display:block;margin-bottom:6px;color:#4a5568;}
input{width:100%;padding:10px 12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;}
.err-text{color:#dc2626;font-size:13px;margin-top:4px;display:block;}
button{width:100%;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
button:hover{background:#1d4ed8;}
.link{text-align:center;margin-top:16px;font-size:14px;}
.link a{color:#2563eb;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
    <h2>新用户注册</h2>
    <?php if(!empty($errors['global'])): ?>
        <div class="global-err"><?=htmlspecialchars($errors['global'],ENT_QUOTES)?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_reg'],ENT_QUOTES)?>">
        <div class="item">
            <label>用户名</label>
            <input type="text" name="username" value="<?=htmlspecialchars($fill['username'],ENT_QUOTES)?>" placeholder="至少3字符">
            <?php if(!empty($errors['username'])):?><span class="err-text"><?=$errors['username']?></span><?php endif; ?>
        </div>
        <div class="item">
            <label>密码</label>
            <input type="password" name="password" placeholder="最少6位">
            <?php if(!empty($errors['password'])):?><span class="err-text"><?=$errors['password']?></span><?php endif; ?>
        </div>
        <div class="item">
            <label>确认密码</label>
            <input type="password" name="password2">
            <?php if(!empty($errors['password2'])):?><span class="err-text"><?=$errors['password2']?></span><?php endif; ?>
        </div>
        <div class="item">
            <label>邮箱</label>
            <input type="email" name="email" value="<?=htmlspecialchars($fill['email'],ENT_QUOTES)?>">
            <?php if(!empty($errors['email'])):?><span class="err-text"><?=$errors['email']?></span><?php endif; ?>
        </div>
        <div class="item">
            <label>头像（选填，1MB内jpg/png）</label>
            <input type="file" name="avatar" accept="image/jpeg,image/png">
            <?php if(!empty($errors['avatar'])):?><span class="err-text"><?=$errors['avatar']?></span><?php endif; ?>
        </div>
        <button type="submit">注册</button>
    </form>
    <div class="link">已有账号？<a href="login.php">去登录</a></div>
</div>
</body>
</html>