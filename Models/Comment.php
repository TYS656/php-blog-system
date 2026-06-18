<?php
namespace Blog\Models;
use Blog\Core\Database;

class Comment
{
    public ?int $id = null;
    public int $postId = 0;
    public int $userId = 0;
    public string $content = '';
    public string $createdAt = '';
    public string $authorName = '';

    // 获取文章所有评论
    public static function getByPostId(int $postId): array
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "SELECT c.*, u.username as authorName 
             FROM comments c
             JOIN users u ON c.user_id = u.id
             WHERE c.post_id = :pid
             ORDER BY c.created_at ASC"
        );
        $stmt->execute([':pid' => $postId]);
        $list = [];
        while ($row = $stmt->fetch()) {
            $comment = new self();
            $comment->id = (int)$row['id'];
            $comment->postId = (int)$row['post_id'];
            $comment->userId = (int)$row['user_id'];
            $comment->content = $row['content'];
            $comment->createdAt = $row['created_at'];
            $comment->authorName = $row['authorName'];
            $list[] = $comment;
        }
        return $list;
    }

    // 新增评论
    public static function create(int $postId, int $userId, string $content): int
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO comments (post_id, user_id, content) VALUES (:pid, :uid, :c)"
        );
        $stmt->execute([
            ':pid' => $postId,
            ':uid' => $userId,
            ':c' => $content
        ]);
        return (int)$pdo->lastInsertId();
    }
    /**
     * 根据ID查询单条评论
     */
    public static function findById(int $id): ?self
    {
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $comment = new self();
        $comment->id = (int)$row['id'];
        $comment->postId = (int)$row['post_id'];
        $comment->userId = (int)$row['user_id'];
        $comment->content = $row['content'];
        $comment->createdAt = $row['created_at'];
        return $comment;
    }

    /**
     * 删除评论
     */
    public function delete(): void
    {
        if (!$this->id) return;
        $pdo = Database::getInstance()->getPdo();
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = :id");
        $stmt->execute([':id' => $this->id]);
    }
}