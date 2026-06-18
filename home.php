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
use Blog\Models\Post;

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$postList = Post::getPaginated($page, $perPage);
$totalPosts = Post::getTotalCount();
$totalPages = max(1, ceil($totalPosts / $perPage));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>全部文章</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f5f7fa;padding:40px 20px;}
.wrap{max-width:720px;margin:0 auto;}
.top{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;}
h1{color:#2d3748;font-size:24px;}
.btn{padding:8px 16px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-size:14px;}
.btn.gray{background:#666;}
.item{background:#fff;padding:16px;border-radius:10px;box-shadow:0 1px 8px rgba(0,0,0,0.06);margin-bottom:16px;display:flex;gap:16px;align-items:center;}
.item-cover{width:140px;height:90px;object-fit:cover;border-radius:6px;flex-shrink:0;background:#f1f5f9;}
.item-body{flex:1;min-width:0;}
.title{font-size:18px;margin-bottom:8px;}
.title a{color:#2563eb;text-decoration:none;}
.title a:hover{text-decoration:underline;}
.meta{color:#666;font-size:14px;}
.empty{text-align:center;padding:40px;color:#888;background:#fff;border-radius:10px;}

.pagination{display:flex;justify-content:center;align-items:center;gap:12px;margin-top:30px;}
.pagination a, .pagination span{padding:8px 14px;border-radius:6px;text-decoration:none;font-size:14px;}
.pagination a{background:#fff;color:#2563eb;border:1px solid #e2e8f0;}
.pagination a:hover{background:#2563eb;color:#fff;}
.pagination .current{background:#2563eb;color:#fff;}
.pagination .disabled{color:#aaa;background:#f1f5f9;border:1px solid #e2e8f0;cursor:not-allowed;}
</style>
</head>
<body>
<div class="wrap">
    <div class="top">
        <h1>文章列表</h1>
        <div>
            <?php if(!empty($_SESSION['logged_in'])): ?>
                <a href="create_post.php" class="btn">发布文章</a>
                <a href="welcome.php" class="btn gray" style="margin-left:8px;">个人中心</a>
            <?php else: ?>
                <a href="login.php" class="btn">登录</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if(empty($postList)): ?>
        <div class="empty">暂无文章</div>
    <?php else: ?>
        <?php foreach($postList as $p): ?>
        <div class="item">
            <?php if(!empty($p->cover) && file_exists($p->cover)): ?>
                <img class="item-cover" src="<?=htmlspecialchars($p->cover)?>" alt="文章封面">
            <?php else: ?>
                <div class="item-cover"></div>
            <?php endif; ?>
            <div class="item-body">
                <div class="title">
                    <a href="view_post.php?id=<?=$p->id?>"><?=htmlspecialchars($p->title)?></a>
                </div>
                <div class="meta">作者：<?=htmlspecialchars($p->authorName)?> · 发布于 <?=$p->createdAt?></div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- 分页导航 -->
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?=$page-1?>">上一页</a>
            <?php else: ?>
                <span class="disabled">上一页</span>
            <?php endif; ?>

            <span class="current">第 <?=$page?> / <?=$totalPages?> 页</span>

            <?php if($page < $totalPages): ?>
                <a href="?page=<?=$page+1?>">下一页</a>
            <?php else: ?>
                <span class="disabled">下一页</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>