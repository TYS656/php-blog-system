# PHP 原生博客系统
基于 PHP 原生 + PDO + MySQL 开发的轻量化博客系统，全程面向对象设计，内置完整的用户体系、内容管理、交互通知功能。

## 功能特性
### 用户体系
- 用户注册/登录/退出、账号注销
- 图形验证码防暴力破解
- 个人资料修改、头像上传
- 密码修改、忘记密码重置（令牌机制）

### 内容体系
- 文章发布/编辑/删除
- 文章封面图上传
- 文章列表分页
- 文章详情页展示

### 交互体系
- 文章评论、评论删除
- 文章点赞/收藏（切换式）
- 评论点赞
- 站内通知系统（点赞通知、收藏通知、评论点赞通知）
- 通知一键清空、全部标记已读

### 安全特性
- PDO 预处理防SQL注入
- 全局 XSS 转义防护
- CSRF 令牌校验
- 文件上传类型校验
- 权限边界控制

## 环境要求
- PHP >= 7.4
- MySQL >= 5.7
- 开启 GD、PDO_MYSQL 扩展
- Apache / Nginx  Web服务

## 部署步骤
1. 克隆项目到网站根目录
2. 复制`config.php`，填写数据库连接信息
3. 新建数据库，导入 `install.sql` 建表
4. 给 `uploads/` 目录赋予写入权限
5. 访问 `home.php` 即可使用

## 目录结构
blog/
├── Core/                     # 核心类库
│ └── Database.php # 数据库单例类
├── Models/                   # 数据模型层
│ ├── User.php
│ ├── Post.php
│ ├── Comment.php
│ ├── Like.php
│ ├── Favorite.php
│ ├── CommentLike.php
│ └── Notification.php
├── uploads/                  # 上传文件目录
│ ├── avatar/
│ └── post/
├── config.php                # 配置示例
├── install.sql               # 数据库安装脚本
├── captcha.php               # 图形验证码生成
├── register.php              # 用户注册
├── login.php                 # 用户登录
├── welcome.php               # 个人中心首页
├── profile.php               # 修改个人资料
├── change_password.php       # 修改登录密码
├── forgot_password.php       # 忘记密码入口
├── reset_password.php        # 重置密码页面
├── home.php                  # 文章列表（分页）
├── create_post.php           # 发布文章
├── view_post.php             # 文章详情
├── edit_post.php             # 编辑文章
├── delete_post.php           # 删除文章
├── add_comment.php           # 提交评论
├── delete_comment.php        # 删除评论
├── toggle_like.php           # 点赞切换
├── toggle_favorite.php       # 收藏切换
├── toggle_comment_like.php   # 评论点赞切换
├── my_favorites.php          # 我的收藏列表
├── notifications.php         # 我的通知列表
├── delete_user.php           # 注销账号
└── logout.php                # 退出登录
## 开源协议
MIT License