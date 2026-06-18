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
use Blog\Models\Notification;

if (empty($_SESSION['logged_in'])) {
    header("Location: login.php", true, 302);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'readall') {
    Notification::markAllRead($_SESSION['user_id']);
    header("Location: notifications.php", true, 302);
    exit;
}
// 一键清空所有通知
if (isset($_GET['action']) && $_GET['action'] === 'clearall') {
    Notification::clearAll($_SESSION['user_id']);
    header("Location: notifications.php", true, 302);
    exit;
}
$list = Notification::getUserAll($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>我的通知</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f5f7fa;padding:40px 20px;}
.wrap{max-width:640px;margin:0 auto;}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;}
h1{color:#2d3748;font-size:22px;}
.btn{padding:6px 14px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-size:14px;}
.btn.gray{background:#666;}
.btn.red{background:#dc2626;}
.item{background:#fff;padding:14px 20px;border-radius:8px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;}
.item.unread{border-left:4px solid #2563eb;}
.item-content{color:#333;}
.item-content strong{color:#2563eb;}
.item-time{color:#999;font-size:13px;flex-shrink:0;margin-left:20px;}
.empty{text-align:center;padding:40px;color:#888;background:#fff;border-radius:10px;}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>我的通知</h1>
        <div>
            <a href="?action=readall" class="btn">全部标记已读</a>
            <a href="?action=clearall" class="btn red" style="margin-left:8px;" onclick="return confirm('确定清空所有通知？')">一键清空</a>
            <a href="welcome.php" class="btn gray" style="margin-left:8px;">返回</a>
        </div>
    </div>

    <?php if(empty($list)): ?>
        <div class="empty">暂无通知</div>
    <?php else: ?>
        <?php foreach($list as $item): ?>
        <div class="item <?=$item->isRead ? '' : 'unread'?>">
            <div class="item-content">
                <strong><?=htmlspecialchars($item->triggerUserName)?></strong>
                <?=htmlspecialchars($item->content)?>
            </div>
            <div class="item-time"><?=$item->createdAt?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>