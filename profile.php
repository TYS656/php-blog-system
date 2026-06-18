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
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: DENY");

if (empty($_SESSION['logged_in'])) {
    header("Location: login.php", true, 302);
    exit;
}
use Blog\Models\User;

$currentUser = User::findById($_SESSION['user_id']);
if (empty($_SESSION['csrf_profile'])) {
    $_SESSION['csrf_profile'] = bin2hex(random_bytes(32));
}

$success = '';
$error = '';
$uploadDir = __DIR__ . '/uploads/avatar/';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $newAvatar = $currentUser->avatar;

    if (!hash_equals($_SESSION['csrf_profile'], $token)) {
        $error = "非法请求";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "邮箱格式错误";
    } else {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['avatar'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (in_array($mime, ['image/jpeg','image/png']) && $file['size'] <= 1048576) {
                    $ext = $mime === 'image/png' ? 'png' : 'jpg';
                    $name = md5(uniqid(true).$currentUser->username).".".$ext;
                    move_uploaded_file($file['tmp_name'], $uploadDir.$name);
                    $newAvatar = "uploads/avatar/".$name;
                } else {
                    $error = "头像仅支持jpg/png，不超过1MB";
                }
            }
        }

        if (empty($error)) {
            $currentUser->updateProfile($email, $newAvatar);
            $_SESSION['avatar'] = $newAvatar;
            $success = "资料修改成功";
            $currentUser = User::findById($_SESSION['user_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>修改个人资料</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;padding:60px 20px;}
.card{width:460px;margin:0 auto;background:#fff;padding:36px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.07);}
h2{text-align:center;margin-bottom:24px;color:#2d3748;}
.msg{padding:10px;border-radius:6px;margin-bottom:16px;text-align:center;}
.msg.err{background:#fee;color:#dc2626;}
.msg.ok{background:#f0fdf4;color:#16a34a;}
.item{margin-bottom:18px;}
label{display:block;margin-bottom:6px;color:#4a5568;}
input{width:100%;padding:10px 12px;border:1px solid #cbd5e0;border-radius:6px;font-size:15px;}
.disabled{background:#f1f5f9;color:#666;}
.avatar-preview{width:100px;height:100px;border-radius:50%;object-fit:cover;margin:10px auto;display:block;border:2px solid #2563eb;}
button{width:100%;padding:11px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
button:hover{background:#1d4ed8;}
.back{display:block;text-align:center;margin-top:16px;color:#666;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
    <h2>修改个人资料</h2>
    <?php if($error): ?><div class="msg err"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <?php if($success): ?><div class="msg ok"><?=htmlspecialchars($success)?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_profile'])?>">
        
        <div class="item">
            <label>用户名（不可修改）</label>
            <input type="text" value="<?=htmlspecialchars($currentUser->username)?>" class="disabled" disabled>
        </div>
        <div class="item">
            <label>邮箱</label>
            <input type="email" name="email" value="<?=htmlspecialchars($currentUser->email)?>">
        </div>
        <div class="item">
            <label>头像</label>
            <?php if(!empty($currentUser->avatar) && file_exists($currentUser->avatar)): ?>
                <img class="avatar-preview" src="<?=htmlspecialchars($currentUser->avatar)?>" alt="当前头像">
            <?php endif; ?>
            <input type="file" name="avatar" accept="image/jpeg,image/png">
            <p style="font-size:12px;color:#999;margin-top:4px;">不上传则保留原头像</p>
        </div>
        <button type="submit">保存修改</button>
    </form>
    <a class="back" href="welcome.php">返回个人中心</a>
</div>
</body>
</html>