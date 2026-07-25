# MySQL 5.7.43 上线步骤

## 服务器要求

- MySQL 5.7.43
- PHP 7.4 或更高版本
- PHP 扩展：PDO、pdo_mysql、session、json
- Nginx 或 Apache
- HTTPS 证书

## 快速部署

1. 在服务器面板创建 MySQL 数据库，字符集选择 `utf8mb4`。
2. 选中数据库并执行 `mysql-schema-5.7.sql`。
3. 复制 `api/config.example.php` 为 `api/config.php`。
4. 在 `api/config.php` 中填写 MySQL 地址、端口、数据库名、账号和密码。
5. 复制 `cloud-config.example.js` 为 `cloud-config.js`。
6. 将整个项目目录上传到网站根目录。
7. 访问 `/api/index.php?action=health`，确认返回 MySQL 版本。
8. 打开网页，使用“首次使用，创建账号”建立第一个账号。
9. 第一个账号创建后，系统会自动关闭继续注册。
10. 从旧系统导出 JSON 完整备份，再在新系统中导入。

数据库密码只能保存在服务器的 `api/config.php`，绝对不能写入 JavaScript。

GitHub Pages 无法运行 PHP，因此 MySQL 模式必须部署到支持 PHP 的服务器；现有 GitHub Pages 只能继续使用 Supabase 回退配置。
