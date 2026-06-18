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
use Blog\Models\Comment;
use Blog\Models\Like;
use Blog\Models\Favorite;
use Blog\Models\CommentLike;

$id = (int)($_GET['id'] ?? 0);
$post = Post::find($id);
if(!$post){
    header("Location: home.php", true, 302);
    exit;
}

$isAuthor = !empty($_SESSION['logged_in']) && $_SESSION['user_id'] == $post->userId;
$comments = Comment::getByPostId($id);
$likeCount = Like::getCount($id);
$favoriteCount = Favorite::getCount($id);

$isLiked = false;
$isFavorited = false;
if (!empty($_SESSION['logged_in'])) {
    $isLiked = Like::checkUserLike($id, $_SESSION['user_id']);
    $isFavorited = Favorite::checkUserFavorite($id, $_SESSION['user_id']);
}

if (empty($_SESSION['csrf_interact'])) {
    $_SESSION['csrf_interact'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_interact'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?=htmlspecialchars($post->title)?> - 我的博客</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Microsoft YaHei", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;}
body{
    background:#f0f2f5;
    padding:40px 20px;
    line-height:1.6;
    color:#1f2937;
}

.container{max-width:800px;margin:0 auto;}

.post-card{
    background:#fff;
    border-radius:16px;
    box-shadow:0 4px 20px rgba(0,0,0,0.06);
    padding:48px;
    margin-bottom:32px;
}

.post-cover{
    width:100%;
    max-height:380px;
    object-fit:cover;
    border-radius:12px;
    margin-bottom:32px;
}

.post-title{
    font-size:28px;
    font-weight:600;
    color:#111827;
    line-height:1.4;
    text-align:center;
    margin-bottom:16px;
}

.post-meta{
    text-align:center;
    color:#6b7280;
    font-size:14px;
    padding-bottom:24px;
    margin-bottom:32px;
    border-bottom:1px solid #f3f4f6;
}
.post-meta span{margin:0 8px;}

.post-content{
    font-size:16px;
    color:#374151;
    line-height:1.8;
    word-break:break-word;
    white-space:pre-wrap;
}
.post-content p{margin-bottom:16px;}

.interact-bar{
    display:flex;
    justify-content:center;
    gap:16px;
    margin:40px 0;
    padding:24px 0;
    border-top:1px solid #f3f4f6;
    border-bottom:1px solid #f3f4f6;
}
.interact-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 24px;
    border-radius:50px;
    border:1px solid #e5e7eb;
    background:#fff;
    cursor:pointer;
    color:#4b5563;
    font-size:15px;
    transition:all 0.2s ease;
}
.interact-btn:hover{
    background:#f9fafb;
    transform:translateY(-1px);
}
.interact-btn.active{
    background:#2563eb;
    color:#fff;
    border-color:#2563eb;
}
.interact-btn.active:hover{background:#1d4ed8;}
.interact-btn:disabled{opacity:0.6;cursor:not-allowed;}

.btn-group{
    text-align:center;
    margin-bottom:20px;
}
.btn{
    display:inline-block;
    padding:9px 22px;
    margin:0 6px;
    text-decoration:none;
    border-radius:8px;
    font-size:14px;
    transition:opacity 0.2s;
    color:#fff;
    border:none;
    cursor:pointer;
}
.btn:hover{opacity:0.88;}
.btn-edit{background:#059669;}
.btn-del{background:#dc2626;}
.btn-back{background:#6b7280;}

.comment-card{
    background:#fff;
    border-radius:16px;
    box-shadow:0 4px 20px rgba(0,0,0,0.06);
    padding:36px 48px;
}
.comment-title{
    font-size:20px;
    font-weight:600;
    color:#111827;
    margin-bottom:24px;
    padding-bottom:16px;
    border-bottom:1px solid #f3f4f6;
}

.comment-list{margin-bottom:32px;}
.comment-item{
    padding:20px 0;
    border-bottom:1px solid #f9fafb;
    transition:opacity 0.3s;
}
.comment-item:last-child{border-bottom:none;}
.comment-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:10px;
}
.comment-user{
    display:flex;
    align-items:center;
    gap:10px;
}
.comment-avatar{
    width:36px;
    height:36px;
    border-radius:50%;
    background:#e0e7ff;
    color:#4338ca;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:14px;
    font-weight:500;
}
.comment-name{
    font-weight:500;
    color:#111827;
    font-size:15px;
}
.comment-time{
    color:#9ca3af;
    font-size:13px;
}
.comment-content{
    color:#374151;
    font-size:15px;
    line-height:1.7;
    margin-left:46px;
    margin-bottom:12px;
}
.comment-footer{
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:16px;
    margin-left:46px;
}
.comment-like-btn{
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:4px 10px;
    border-radius:4px;
    font-size:13px;
    color:#6b7280;
    border:none;
    background:none;
    cursor:pointer;
    transition:color 0.2s;
}
.comment-like-btn:hover{color:#2563eb;}
.comment-like-btn.active{color:#2563eb;}
.comment-del-btn{
    font-size:13px;
    color:#9ca3af;
    text-decoration:none;
    background:none;
    border:none;
    cursor:pointer;
}
.comment-del-btn:hover{color:#dc2626;}

.comment-empty{
    text-align:center;
    padding:40px 0;
    color:#9ca3af;
    font-size:14px;
}

.comment-form{
    border-top:1px solid #f3f4f6;
    padding-top:24px;
}
.comment-form textarea{
    width:100%;
    min-height:100px;
    padding:12px 16px;
    border:1px solid #e5e7eb;
    border-radius:10px;
    resize:vertical;
    font-size:15px;
    line-height:1.6;
    margin-bottom:12px;
    outline:none;
    transition:border-color 0.2s;
}
.comment-form textarea:focus{border-color:#2563eb;}
.comment-form button{
    padding:9px 24px;
    background:#2563eb;
    color:#fff;
    border:none;
    border-radius:8px;
    font-size:15px;
    cursor:pointer;
    transition:background 0.2s;
}
.comment-form button:hover{background:#1d4ed8;}
.login-tip{
    text-align:center;
    padding:30px 0;
    color:#6b7280;
    font-size:14px;
}
.login-tip a{
    color:#2563eb;
    text-decoration:none;
    font-weight:500;
}

.tip-toast{
    position:fixed;
    top:30px;
    left:50%;
    transform:translateX(-50%);
    padding:10px 20px;
    background:rgba(0,0,0,0.75);
    color:#fff;
    border-radius:6px;
    font-size:14px;
    z-index:9999;
    opacity:0;
    transition:opacity 0.3s;
    pointer-events:none;
}
.tip-toast.show{opacity:1;}

@media (max-width: 768px){
    body{padding:20px 12px;}
    .post-card, .comment-card{padding:24px 20px;}
    .post-title{font-size:22px;}
    .interact-btn{padding:8px 18px;font-size:14px;}
}
</style>
</head>
<body>
<div class="container">
    <article class="post-card">
        <?php if(!empty($post->cover) && file_exists($post->cover)): ?>
            <img class="post-cover" src="<?=htmlspecialchars($post->cover)?>" alt="<?=htmlspecialchars($post->title)?>">
        <?php endif; ?>

        <h1 class="post-title"><?=htmlspecialchars($post->title)?></h1>
        <div class="post-meta">
            <span>作者：<?=htmlspecialchars($post->authorName)?></span>
            <span>·</span>
            <span>发布于 <?=$post->createdAt?></span>
        </div>

        <div class="post-content"><?=nl2br(htmlspecialchars($post->content))?></div>

        <div class="interact-bar">
            <button class="interact-btn js-post-like <?=$isLiked ? 'active' : ''?>" data-id="<?=$post->id?>">
                👍 点赞 <span class="like-count"><?=$likeCount?></span>
            </button>
            <button class="interact-btn js-post-favorite <?=$isFavorited ? 'active' : ''?>" data-id="<?=$post->id?>">
                ⭐ 收藏 <span class="favorite-count"><?=$favoriteCount?></span>
            </button>
        </div>

        <div class="btn-group">
            <?php if($isAuthor): ?>
                <a href="edit_post.php?id=<?=$post->id?>" class="btn btn-edit">编辑文章</a>
                <a href="delete_post.php?id=<?=$post->id?>" class="btn btn-del">删除文章</a>
            <?php endif; ?>
            <a href="home.php" class="btn btn-back">返回列表</a>
        </div>
    </article>

    <div class="comment-card">
        <h2 class="comment-title">评论区（<span class="comment-total"><?=count($comments)?></span>）</h2>

        <div class="comment-list js-comment-list">
            <?php if(empty($comments)): ?>
                <div class="comment-empty">暂无评论，快来抢沙发吧~</div>
            <?php else: ?>
                <?php foreach($comments as $c):
                    $commentLikeCount = CommentLike::getCount($c->id);
                    $isCommentLiked = false;
                    if (!empty($_SESSION['logged_in'])) {
                        $isCommentLiked = CommentLike::checkUserLike($c->id, $_SESSION['user_id']);
                    }
                    $canDelete = $isAuthor || (!empty($_SESSION['logged_in']) && $_SESSION['user_id'] == $c->userId);
                    $firstChar = mb_substr($c->authorName, 0, 1);
                ?>
                <div class="comment-item js-comment-item" data-id="<?=$c->id?>">
                    <div class="comment-header">
                        <div class="comment-user">
                            <div class="comment-avatar"><?=htmlspecialchars($firstChar)?></div>
                            <span class="comment-name"><?=htmlspecialchars($c->authorName)?></span>
                        </div>
                        <span class="comment-time"><?=$c->createdAt?></span>
                    </div>
                    <div class="comment-content"><?=nl2br(htmlspecialchars($c->content))?></div>
                    <div class="comment-footer">
                        <?php if(!empty($_SESSION['logged_in'])): ?>
                            <button class="comment-like-btn js-comment-like <?=$isCommentLiked ? 'active' : ''?>" data-id="<?=$c->id?>">
                                👍 <span class="comment-like-count"><?=$commentLikeCount?></span>
                            </button>
                        <?php else: ?>
                            <span class="comment-like-btn">👍 <?=$commentLikeCount?></span>
                        <?php endif; ?>
                        
                        <?php if($canDelete): ?>
                            <button class="comment-del-btn js-delete-comment" data-id="<?=$c->id?>">删除</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if(!empty($_SESSION['logged_in'])): ?>
            <div class="comment-form">
                <form method="post" action="add_comment.php">
                    <input type="hidden" name="post_id" value="<?=$post->id?>">
                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($csrfToken)?>">
                    <textarea name="content" placeholder="写下你的评论..." required></textarea>
                    <div style="text-align:right;">
                        <button type="submit">发表评论</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <p class="login-tip">
                <a href="login.php">登录</a> 后即可发表评论、点赞和收藏文章
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="tip-toast js-toast"></div>

<script>
const csrfToken = '<?=htmlspecialchars($csrfToken)?>';
const isLoggedIn = <?= !empty($_SESSION['logged_in']) ? 'true' : 'false' ?>;
const isPostAuthor = <?= $isAuthor ? 'true' : 'false' ?>;

// 提示框
function showToast(msg) {
    const toast = document.querySelector('.js-toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2000);
}

// 通用AJAX请求
function ajaxPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams(data).toString()
    }).then(res => res.json());
}

// 生成单条评论HTML
function buildCommentHtml(comment) {
    const firstChar = comment.author_name.charAt(0);
    const deleteBtn = comment.can_delete || isPostAuthor
        ? `<button class="comment-del-btn js-delete-comment" data-id="${comment.id}">删除</button>`
        : '';
    const likeBtn = isLoggedIn
        ? `<button class="comment-like-btn js-comment-like" data-id="${comment.id}">
            👍 <span class="comment-like-count">${comment.like_count}</span>
        </button>`
        : `<span class="comment-like-btn">👍 ${comment.like_count}</span>`;

    return `
    <div class="comment-item js-comment-item" data-id="${comment.id}" style="opacity:0;transition:opacity 0.3s;">
        <div class="comment-header">
            <div class="comment-user">
                <div class="comment-avatar">${firstChar}</div>
                <span class="comment-name">${comment.author_name}</span>
            </div>
            <span class="comment-time">${comment.created_at}</span>
        </div>
        <div class="comment-content">${comment.content.replace(/\n/g, '<br>')}</div>
        <div class="comment-footer">
            ${likeBtn}
            ${deleteBtn}
        </div>
    </div>`;
}

// ========== 文章点赞 ==========
document.querySelector('.js-post-like').addEventListener('click', function() {
    if (!isLoggedIn) { showToast('请先登录'); return; }
    const btn = this;
    const postId = btn.dataset.id;
    btn.disabled = true;

    ajaxPost('toggle_like.php', {
        post_id: postId,
        csrf_token: csrfToken
    }).then(res => {
        btn.disabled = false;
        if (res.code === 1) {
            btn.classList.toggle('active', res.is_liked);
            btn.querySelector('.like-count').textContent = res.count;
            showToast(res.is_liked ? '点赞成功' : '已取消点赞');
        } else {
            showToast(res.msg || '操作失败');
        }
    }).catch(() => {
        btn.disabled = false;
        showToast('网络错误');
    });
});

// ========== 文章收藏 ==========
document.querySelector('.js-post-favorite').addEventListener('click', function() {
    if (!isLoggedIn) { showToast('请先登录'); return; }
    const btn = this;
    const postId = btn.dataset.id;
    btn.disabled = true;

    ajaxPost('toggle_favorite.php', {
        post_id: postId,
        csrf_token: csrfToken
    }).then(res => {
        btn.disabled = false;
        if (res.code === 1) {
            btn.classList.toggle('active', res.is_favorited);
            btn.querySelector('.favorite-count').textContent = res.count;
            showToast(res.is_favorited ? '收藏成功' : '已取消收藏');
        } else {
            showToast(res.msg || '操作失败');
        }
    }).catch(() => {
        btn.disabled = false;
        showToast('网络错误');
    });
});

// ========== 评论点赞（事件委托，兼容动态新增） ==========
document.querySelector('.js-comment-list').addEventListener('click', function(e) {
    const btn = e.target.closest('.js-comment-like');
    if (!btn) return;
    if (!isLoggedIn) { showToast('请先登录'); return; }

    const commentId = btn.dataset.id;
    btn.disabled = true;

    ajaxPost('toggle_comment_like.php', {
        comment_id: commentId,
        csrf_token: csrfToken
    }).then(res => {
        btn.disabled = false;
        if (res.code === 1) {
            btn.classList.toggle('active', res.is_liked);
            btn.querySelector('.comment-like-count').textContent = res.count;
        } else {
            showToast(res.msg || '操作失败');
        }
    }).catch(() => {
        btn.disabled = false;
        showToast('网络错误');
    });
});

// ========== 删除评论（事件委托，兼容动态新增） ==========
document.querySelector('.js-comment-list').addEventListener('click', function(e) {
    const btn = e.target.closest('.js-delete-comment');
    if (!btn) return;

    const commentId = btn.dataset.id;
    if (!confirm('确定删除这条评论？')) return;

    ajaxPost('delete_comment.php', {
        id: commentId,
        csrf_token: csrfToken
    }).then(res => {
        if (res.code === 1) {
            const item = document.querySelector(`.js-comment-item[data-id="${commentId}"]`);
            if (item) {
                item.style.opacity = '0';
                setTimeout(() => {
                    item.remove();
                    updateCommentTotal();
                }, 300);
            }
            showToast('删除成功');
        } else {
            showToast(res.msg || '删除失败');
        }
    }).catch(() => showToast('网络错误'));
});

// ========== 发表评论（无刷新） ==========
const commentForm = document.querySelector('.comment-form form');
if (commentForm) {
    commentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const textarea = this.querySelector('textarea[name="content"]');
        const submitBtn = this.querySelector('button[type="submit"]');
        const content = textarea.value.trim();

        if (!content) {
            showToast('评论内容不能为空');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = '发布中...';

        ajaxPost('add_comment.php', {
            post_id: <?= $post->id ?>,
            content: content,
            csrf_token: csrfToken
        }).then(res => {
            submitBtn.disabled = false;
            submitBtn.textContent = '发表评论';

            if (res.code === 1) {
                const list = document.querySelector('.js-comment-list');
                // 清空空状态
                if (list.querySelector('.comment-empty')) {
                    list.innerHTML = '';
                }
                // 追加新评论
                list.insertAdjacentHTML('beforeend', buildCommentHtml(res.data));
                // 淡入动画
                setTimeout(() => {
                    const newItem = list.lastElementChild;
                    if (newItem) newItem.style.opacity = '1';
                }, 10);
                // 清空输入框
                textarea.value = '';
                // 更新总数
                updateCommentTotal();
                // 滚动到新评论
                list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                showToast('评论发表成功');
            } else {
                showToast(res.msg || '评论失败');
            }
        }).catch(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = '发表评论';
            showToast('网络错误');
        });
    });
}

// 更新评论总数
function updateCommentTotal() {
    const total = document.querySelectorAll('.js-comment-item').length;
    document.querySelector('.comment-total').textContent = total;
    if (total === 0) {
        document.querySelector('.js-comment-list').innerHTML = '<div class="comment-empty">暂无评论，快来抢沙发吧~</div>';
    }
}
</script>
</body>
</html>