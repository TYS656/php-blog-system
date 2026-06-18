<?php
namespace Blog\Models;
use Blog\Core\Database;

class Like
{
    // 获取点赞数
    public static function getCount(int $postId): int
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = :pid");
        $stmt->execute([':pid' => $postId]);
        return (int)$stmt->fetchColumn();
    }

    // 判断用户是否已点赞
    public static function checkUserLike(int $postId, int $userId): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT id FROM post_likes WHERE post_id = :pid AND user_id = :uid LIMIT 1"
        );
        $stmt->execute([':pid' => $postId, ':uid' => $userId]);
        return $stmt->fetch() !== false;
    }

    // 切换点赞状态，返回当前是否点赞
    public static function toggle(int $postId, int $userId): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $isLiked = self::checkUserLike($postId, $userId);
        
        if ($isLiked) {
            $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = :pid AND user_id = :uid");
            $stmt->execute([':pid' => $postId, ':uid' => $userId]);
            return false;
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (:pid, :uid)");
                $stmt->execute([':pid' => $postId, ':uid' => $userId]);
                return true;
            } catch (\PDOException $e) {
                return true;
            }
        }
    }
}