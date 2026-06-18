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
use Blog\Models\Favorite;

if (empty($_SESSION['logged_in'])) {
    header("Location: login.php", true, 302);
    exit;
}

$favoriteList = Favorite::getUserFavorites($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>我的收藏</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f5f7fa;padding:40px 20px;}
.wrap{max-width:720px;margin:0 auto;}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
h1{color:#2d3748;font-size:22px;}
.btn{padding:8px 16px;background:#666;color:#fff;text-decoration:none;border-radius:6px;font-size:14px;}
.item{background:#fff;padding:16px;border-radius:10px;box-shadow:0 1px 8px rgba(0,0,0,0.06);margin-bottom:16px;}
.title{font-size:17px;margin-bottom:8px;}
.title a{color:#2563eb;text-decoration:none;}
.meta{color:#666;font-size:14px;}
.empty{text-align:center;padding:40px;color:#888;background:#fff;border-radius:10px;}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>我的收藏</h1>
        <a href="welcome.php" class="btn">返回个人中心</a>
    </div>

    <?php if(empty($favoriteList)): ?>
        <div class="empty">你还没有收藏任何文章，快去发现好文吧~</div>
    <?php else: ?>
        <?php foreach($favoriteList as $p): ?>
        <div class="item">
            <div class="title">
                <a href="view_post.php?id=<?=$p->id?>"><?=htmlspecialchars($p->title)?></a>
            </div>
            <div class="meta">作者：<?=htmlspecialchars($p->authorName)?> · 收藏于 <?=$p->createdAt?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>