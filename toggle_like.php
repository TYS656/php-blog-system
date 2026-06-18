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
use Blog\Models\Like;
use Blog\Models\Post;
use Blog\Models\Notification;

if (empty($_SESSION['logged_in'])) {
    echo json_encode(['code' => 0, 'msg' => '请先登录']);
    exit;
}

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    
    if (!hash_equals($_SESSION['csrf_interact'] ?? '', $token)) {
        echo json_encode(['code' => 0, 'msg' => '非法请求']);
        exit;
    }
    
    if ($postId <= 0) {
        echo json_encode(['code' => 0, 'msg' => '参数错误']);
        exit;
    }

    $isLikedAfter = Like::toggle($postId, $_SESSION['user_id']);
    $likeCount = Like::getCount($postId);

    // 点赞成功时发送通知
    if ($isLikedAfter) {
        $post = Post::find($postId);
        if ($post) {
            $shortTitle = mb_substr($post->title, 0, 20);
            if (mb_strlen($post->title) > 20) $shortTitle .= '…';
            $content = "赞了你的文章《".$shortTitle."》";
            Notification::send($post->userId, 'like', $_SESSION['user_id'], $postId, $content);
        }
    }

    if ($isAjax) {
        echo json_encode([
            'code' => 1,
            'is_liked' => $isLikedAfter,
            'count' => $likeCount
        ]);
        exit;
    } else {
        header("Location: view_post.php?id=$postId", true, 302);
        exit;
    }
}