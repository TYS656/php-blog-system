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
use Blog\Models\Comment;

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['code' => 0, 'msg' => '请先登录']);
    exit;
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
$postId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    // 校验
    if (!hash_equals($_SESSION['csrf_interact'] ?? '', $token)) {
        $msg = '非法请求';
        if ($isAjax) {
            echo json_encode(['code' => 0, 'msg' => $msg]);
            exit;
        } else {
            die($msg);
        }
    }

    if ($postId <= 0 || empty($content)) {
        $msg = '评论内容不能为空';
        if ($isAjax) {
            echo json_encode(['code' => 0, 'msg' => $msg]);
            exit;
        } else {
            header("Location: view_post.php?id=$postId");
            exit;
        }
    }

    // 插入评论
    $commentId = Comment::create($postId, $_SESSION['user_id'], $content);

    if ($isAjax) {
        // AJAX模式：返回完整评论数据供前端渲染
        echo json_encode([
            'code' => 1,
            'msg' => '评论成功',
            'data' => [
                'id' => $commentId,
                'author_name' => $_SESSION['username'],
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s'),
                'like_count' => 0,
                'can_delete' => true // 自己发的评论默认可删除
            ]
        ]);
        exit;
    } else {
        // 普通表单模式：跳转回原页面
        header("Location: view_post.php?id=$postId", true, 302);
        exit;
    }
}

header("Location: home.php", true, 302);
exit;