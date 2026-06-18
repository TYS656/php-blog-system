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
use Blog\Models\Notification;

$userName = htmlspecialchars($_SESSION['username'], ENT_QUOTES);
$avatar = $_SESSION['avatar'] ?? '';
$unreadCount = Notification::getUnreadCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>个人中心</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f4f6f9;padding-top:80px;text-align:center;}
.box{width:520px;margin:0 auto;background:#fff;padding:40px 30px;border-radius:12px;box-shadow:0 2px 14px rgba(0,0,0,0.07);}
h2{color:#2d3748;margin-bottom:20px;font-size:22px;}
.avatar-wrap{margin:20px 0 30px;}
.avatar-img{width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid #2563eb;}
.avatar-empty{width:120px;height:120px;border-radius:50%;background:#e2e8f0;display:inline-flex;align-items:center;justify-content:center;color:#666;font-size:14px;}
.btn-wrap{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;}
.btn{display:block;width:100%;height:44px;line-height:44px;font-size:15px;text-decoration:none;border-radius:8px;color:#fff;transition:opacity 0.2s ease;position:relative;}
.btn:hover{opacity:0.88;}
.btn-blue{background:#2563eb;}
.btn-gray{background:#6b7280;grid-column: span 3;}
.btn-red{background:#dc2626;grid-column: span 3;}
.badge{position:absolute;top:-6px;right:10px;background:#dc2626;color:#fff;padding:1px 6px;border-radius:10px;font-size:12px;line-height:1.4;}
</style>
</head>
<body>
<div class="box">
    <h2>欢迎你，<?=$userName?>！</h2>
    <div class="avatar-wrap">
        <?php if(!empty($avatar) && file_exists($avatar)): ?>
            <img class="avatar-img" src="<?=htmlspecialchars($avatar,ENT_QUOTES)?>" alt="用户头像">
        <?php else: ?>
            <div class="avatar-empty">暂无头像</div>
        <?php endif; ?>
    </div>

    <div class="btn-wrap">
        <a href="home.php" class="btn btn-blue">浏览文章</a>
        <a href="create_post.php" class="btn btn-blue">发布文章</a>
        <a href="my_favorites.php" class="btn btn-blue">我的收藏</a>
        <a href="profile.php" class="btn btn-blue">修改资料</a>
        <a href="change_password.php" class="btn btn-blue">修改密码</a>
        <a href="notifications.php" class="btn btn-blue">
            我的通知
            <?php if($unreadCount > 0): ?>
                <span class="badge"><?=$unreadCount?></span>
            <?php endif; ?>
        </a>
        <a href="logout.php" class="btn btn-gray">退出登录</a>
        <a href="delete_user.php" class="btn btn-red">永久注销账号</a>
    </div>
</div>
</body>
</html>