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
if (empty($_SESSION['logged_in'])) {
    header("Location: login.php", true, 302);
    exit;
}
use Blog\Models\Post;

$pid = (int)($_GET['id'] ?? 0);
$post = Post::find($pid);
if(!$post || $post->userId != $_SESSION['user_id']){
    header("Location: home.php", true, 302);
    exit;
}

if (empty($_SESSION['csrf_del'])) {
    $_SESSION['csrf_del'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (hash_equals($_SESSION['csrf_del'], $_POST['csrf_token'] ?? '')) {
        $post->delete();
    }
    header("Location: home.php", true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>删除文章</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Microsoft YaHei;}
body{background:#f5f7fa;padding:100px 20px;}
.card{width:420px;margin:0 auto;background:#fff;padding:36px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);text-align:center;}
h2{color:#dc2626;margin-bottom:16px;}
p{color:#4a5568;margin-bottom:24px;line-height:1.6;}
.btn-group{display:flex;gap:12px;justify-content:center;}
.btn{padding:10px 24px;border-radius:6px;text-decoration:none;font-size:15px;border:none;cursor:pointer;}
.btn.cancel{background:#e2e8f0;color:#2d3748;}
.btn.confirm{background:#dc2626;color:#fff;}
</style>
</head>
<body>
<div class="card">
    <h2>确认删除文章？</h2>
    <p>文章标题：<strong><?=htmlspecialchars($post->title)?></strong><br>删除后无法恢复，评论、点赞、收藏数据也会一并清除</p>
    <div class="btn-group">
        <a href="view_post.php?id=<?=$pid?>" class="btn cancel">取消</a>
        <form method="post" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_del'])?>">
            <button type="submit" class="btn confirm">确认删除</button>
        </form>
    </div>
</div>
</body>
</html>