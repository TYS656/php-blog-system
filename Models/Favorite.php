<?php
namespace Blog\Models;
use Blog\Core\Database;

class Favorite
{
    // 获取收藏数
    public static function getCount(int $postId): int
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM post_favorites WHERE post_id = :pid");
        $stmt->execute([':pid' => $postId]);
        return (int)$stmt->fetchColumn();
    }

    // 判断是否已收藏
    public static function checkUserFavorite(int $postId, int $userId): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT id FROM post_favorites WHERE post_id = :pid AND user_id = :uid LIMIT 1"
        );
        $stmt->execute([':pid' => $postId, ':uid' => $userId]);
        return $stmt->fetch() !== false;
    }

    // 切换收藏状态
    public static function toggle(int $postId, int $userId): bool
    {
        $pdo = Database::getInstance()->getPdo();
        $isFavorited = self::checkUserFavorite($postId, $userId);
        
        if ($isFavorited) {
            $stmt = $pdo->prepare("DELETE FROM post_favorites WHERE post_id = :pid AND user_id = :uid");
            $stmt->execute([':pid' => $postId, ':uid' => $userId]);
            return false;
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO post_favorites (post_id, user_id) VALUES (:pid, :uid)");
                $stmt->execute([':pid' => $postId, ':uid' => $userId]);
                return true;
            } catch (\PDOException $e) {
                return true;
            }
        }
    }

    // 获取用户收藏的所有文章
    public static function getUserFavorites(int $userId): array
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT p.*, u.username as authorName, f.created_at as favorite_time
             FROM post_favorites f
             JOIN posts p ON f.post_id = p.id
             JOIN users u ON p.user_id = u.id
             WHERE f.user_id = :uid
             ORDER BY f.created_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        $list = [];
        while ($row = $stmt->fetch()) {
            $post = new Post();
            $post->id = (int)$row['id'];
            $post->userId = (int)$row['user_id'];
            $post->title = $row['title'];
            $post->cover = $row['cover'];
            $post->content = $row['content'];
            $post->createdAt = $row['created_at'];
            $post->authorName = $row['authorName'] ?? '匿名用户';
            $list[] = $post;
        }
        return $list;
    }
}