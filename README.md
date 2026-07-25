# TK-EDC 每日项目记录

一个以电脑端为主的项目记录、客户资料、经营图表和财务报表系统。前端使用原生 HTML、CSS、JavaScript，正式服务器数据通过 PHP API 保存到 MySQL 5.7.43。

- 目标数据库：MySQL 5.7.43
- 后端要求：PHP 7.4+、PDO MySQL、Session
- 当前模式：单账号、同一账号多设备同步
- 旧 Supabase 接口：保留为数据迁移和线上回退模式

## 主要功能

- 项目新增、编辑、删除、搜索、筛选、排序和分组
- 项目分类、项目名称、负责人、阶段状态和收款状态预设
- 金额、成本、自动利润、客户信息、备注和资料记录
- 不同项目及分类使用不同颜色区分
- 未完成项目自动提醒，每次打开网页提醒一次
- 隐藏已交付、批量修改、批量删除、显示或隐藏栏目
- 表格行高、列宽和手动列宽调整
- 客户联系方式及关联项目记录，只读查看项目详情
- 项目数据图表、分类分布、交付率和项目金额排行
- 每日、每周、每月财务报表及待收款提醒
- 本地自动备份、手动备份、JSON 导入导出
- HTML 报表及 MySQL 5.7 数据脚本导出
- MySQL 账号登录、项目数据和预设云端同步

## 目录结构

```text
project-progress-web/
├─ index.html                         # 页面结构、弹窗和资源入口
├─ styles.css                         # 主界面、表格、图表和财务样式
├─ enhancements.css                  # 提醒、备份、筛选等增强样式
├─ app.js                            # 项目核心、渲染、表单和同步逻辑
├─ enhancements.js                   # 提醒、备份、批量操作和高级筛选
├─ mysql-client.js                   # PHP/MySQL API 前端兼容客户端
├─ cloud-config.js                   # 当前线上配置；迁移完成前仍为 Supabase
├─ cloud-config.example.js           # MySQL API 前端配置模板
├─ cloud-config.supabase.example.js  # 旧 Supabase 回退配置模板
├─ mysql-schema-5.7.sql              # MySQL 5.7.43 正式表结构
├─ supabase-schema.sql               # 旧 Supabase 表结构，仅迁移回退
├─ supabase-migration-v2.sql         # 旧 Supabase 兼容说明
├─ ONLINE-SETUP.md                   # MySQL 快速上线步骤
├─ vercel.json                       # 旧静态部署配置
├─ README.md                         # 本维护文档
├─ .gitignore                        # 排除服务器密码和生成文件
├─ api/
│  ├─ index.php                      # 登录、查询、同步、删除 API
│  └─ config.example.php             # 服务器数据库配置模板
└─ .github/
   └─ workflows/
      └─ pages.yml                   # 旧 GitHub Pages 静态发布流程
```

正式服务器还需要创建：

```text
api/config.php
```

该文件只存在服务器上，已被 `.gitignore` 排除，不能上传到 GitHub 或放进公开源码包。

## 系统架构

```text
浏览器
├─ index.html
├─ styles.css + enhancements.css
├─ app.js + enhancements.js
└─ mysql-client.js
        │ HTTPS + JSON + Session Cookie
        ▼
PHP 7.4+ / api/index.php
        │ PDO MySQL
        ▼
MySQL 5.7.43
├─ users
├─ projects
└─ presets
```

浏览器不会直接连接 MySQL，数据库账号和密码只由 PHP 读取。

## MySQL 5.7.43 部署

### 1. 服务器要求

- MySQL 5.7.43
- PHP 7.4 或更高版本
- PHP 扩展：`PDO`、`pdo_mysql`、`session`、`json`
- Nginx 或 Apache
- HTTPS

### 2. 创建数据库

在服务器面板中创建数据库，建议：

```text
数据库名：tk_edc
字符集：utf8mb4
排序规则：utf8mb4_unicode_ci
```

选中该数据库，执行：

```text
mysql-schema-5.7.sql
```

该脚本会创建：

- `users`：账号与密码哈希
- `projects`：项目记录
- `presets`：预设和云端备份

### 3. 配置 PHP API

复制：

```text
api/config.example.php → api/config.php
```

填写服务器数据库信息：

```php
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'tk_edc',
        'user' => 'tk_edc_user',
        'password' => '数据库密码',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'allowed_origin' => '',
        'session_name' => 'tk_edc_session',
        'first_user_registration_only' => true,
    ],
];
```

同域名部署时 `allowed_origin` 保持空字符串。前端和 API 不同域名时，填写前端完整来源，例如：

```text
https://project.example.com
```

### 4. 切换前端到 MySQL

仓库中的 `cloud-config.js` 已包含安全迁移判断：

- `doubleyoung0509.github.io` 和本地 `file://`：继续使用旧 Supabase
- 其他服务器域名：自动使用同域名的 MySQL API

如果需要取消自动判断，可直接使用：

```js
window.CLOUD_CONFIG = {
  provider: 'mysql',
  apiBaseUrl: './api/index.php'
};
```

数据库账号和密码不能写进 JavaScript。

### 5. 检查 API

访问：

```text
https://你的域名/api/index.php?action=health
```

正常结果示例：

```json
{
  "data": {
    "ok": true,
    "database": "MySQL",
    "version": "5.7.43"
  }
}
```

### 6. 创建第一个账号

打开网页，点击“首次使用，创建账号”。密码至少 8 位。

当前配置仅允许创建第一个账号。账号创建成功后，后续注册请求会被服务器拒绝，符合当前单账号模式。

## Nginx 示例

PHP-FPM Socket 路径需根据服务器版本调整：

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/tk-edc;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
    }

    location = /api/config.php {
        deny all;
    }

    location ~ /\.(git|env) {
        deny all;
    }
}
```

配置完成后申请 HTTPS 证书，并将 HTTP 自动跳转到 HTTPS。

## MySQL 数据表

### users

| 字段 | 说明 |
| --- | --- |
| `id` | 账号 ID |
| `email` | 登录邮箱，唯一 |
| `password_hash` | PHP `password_hash()` 生成的密码哈希 |
| `created_at` | 创建时间 |
| `updated_at` | 更新时间 |

### projects

| 字段 | 说明 |
| --- | --- |
| `user_id` | 所属账号 |
| `id` | 项目编号；与 `user_id` 组成主键 |
| `category` | 项目分类 |
| `project_name` | 项目名称 |
| `amount` | 项目金额 |
| `cost` | 项目成本 |
| `start_date` | 开始时间 |
| `owner` | 负责人 |
| `client` | 客户信息 |
| `payment_status` | 收款状态 |
| `statuses` | 阶段状态 JSON 文本 |
| `files` | 资料 |
| `note` | 备注 |
| `created_at` | 创建时间 |
| `updated_at` | 更新时间 |

### presets

保存项目分类、项目名称、负责人、阶段状态、收款状态及云端备份。`preset_hash` 用于兼容较长的备份内容并避免重复。

## 前端兼容机制

`mysql-client.js` 模拟项目原来使用的 Supabase 客户端接口，因此 `app.js` 的项目渲染和编辑逻辑不需要两套实现。

支持的兼容操作：

- `auth.getSession()`
- `auth.signInWithPassword()`
- `auth.signUp()`
- `auth.signOut()`
- `from().select().order()`
- `from().upsert()`
- `from().delete().eq()`

MySQL 有独立的 `payment_status` 字段。PHP API 会在前端传输时兼容旧版 `__TKEDC_META__:` 编码，避免历史数据和现有界面逻辑失效。

## 从 Supabase 迁移到 MySQL

推荐按以下顺序操作：

1. 在旧网页中进入“备份与恢复”。
2. 下载一份完整 JSON 备份。
3. 再导出一份 MySQL 5.7 数据脚本作为备用。
4. 在服务器执行 `mysql-schema-5.7.sql`。
5. 配置 `api/config.php` 和 MySQL 模式的 `cloud-config.js`。
6. 打开新网站并创建第一个账号。
7. 在新网站导入完整 JSON 备份。
8. 点击“立即同步”。
9. 刷新页面，核对项目数、客户数、金额和预设。
10. 确认无误后再停用旧 Supabase。

如果新 MySQL 账号中没有数据，而当前浏览器仍有本地项目，系统也会尝试自动上传；正式迁移仍建议使用 JSON 备份，便于回退。

## 浏览器本地数据

主要 localStorage 项：

| 键名 | 内容 |
| --- | --- |
| `projectRecords` | 项目记录 |
| `projectPresets` | 栏目预设 |
| `projectBackups` | 最近的本地备份 |
| `projectUiPrefs` | 行高、列宽、分组、排序等偏好 |
| `projectCustomColumnWidths` | 手动调整的列宽 |
| `projectHiddenColumns` | 隐藏栏目 |
| `quickFilter` | 当前快捷筛选 |
| `lastProjectView` | 上次打开的页面 |
| `boardFrom` / `boardTo` | 图表日期范围 |
| `financeFrom` / `financeTo` | 财务日期范围 |

清除浏览器网站数据会删除本地缓存和偏好。执行前应先导出完整 JSON 备份。

## 本地开发

直接双击 `index.html` 可以查看本地模式，但 `file://` 无法完整验证 PHP Session。

验证 MySQL 模式需要使用 PHP Web 服务并配置 MySQL。示例：

```powershell
php -S 127.0.0.1:8080
```

访问：

```text
http://127.0.0.1:8080
```

本项目没有 npm 依赖和构建步骤。

## 数据导出与恢复

网页提供：

- 自动备份：修改记录前保存最近版本
- 手动备份：保存本地和云端
- 导出完整备份：JSON
- 导入完整备份：JSON
- 导出 HTML：独立查看报表
- 导出数据库：MySQL 5.7 数据脚本

导出的 MySQL 数据脚本默认使用：

```sql
SET @tk_edc_user_id := 1;
```

导入前应确认目标账号的实际 `users.id`，必要时修改该数字。

## 缓存版本规则

`index.html` 使用查询参数控制浏览器缓存，例如：

```html
<script src="mysql-client.js?v=20260732"></script>
<script src="app.js?v=20260732"></script>
```

修改 CSS 或 JavaScript 后，必须同步增加对应的 `v=` 版本，否则线上用户可能继续使用旧文件。

检查 JavaScript：

```powershell
node --check app.js
node --check enhancements.js
node --check mysql-client.js
```

检查 PHP：

```bash
php -l api/index.php
php -l api/config.php
```

## GitHub Pages 和 Supabase 回退

GitHub Pages 只能发布静态文件，不能运行 PHP，也不能连接 MySQL。仓库保留 `.github/workflows/pages.yml` 和 Supabase 配置，仅用于迁移期间维持旧网站。

MySQL 正式上线后应使用自有服务器地址，不应把 GitHub Pages 当作 MySQL 后端。

旧 Supabase 配置模板：

```text
cloud-config.supabase.example.js
```

## 常见问题

### 显示“MySQL 未配置”

检查 `cloud-config.js`：

```js
window.CLOUD_CONFIG = {
  provider: 'mysql',
  apiBaseUrl: './api/index.php'
};
```

### 显示“服务器连接失败”

1. 访问 `/api/index.php?action=health`。
2. 检查 PHP 是否启用 PDO MySQL。
3. 检查 `api/config.php` 数据库信息。
4. 检查浏览器 Console 和服务器 PHP 错误日志。
5. 确认网页和 API 使用 HTTPS。

### 显示“数据库操作失败”

1. 确认执行了 `mysql-schema-5.7.sql`。
2. 确认数据库账号拥有 SELECT、INSERT、UPDATE、DELETE 权限。
3. 确认数据库字符集为 `utf8mb4`。
4. 查看 PHP 错误日志中的 PDO 详细错误。

### 账号无法注册

单账号模式只允许注册第一个账号。如果 `users` 表已有账号，系统会拒绝继续注册。

如需重建账号，应先备份数据，再由服务器管理员处理 `users` 表；不要直接删除正式账号。

### 修改后线上没有变化

1. 增加 `index.html` 中对应资源的 `v=` 版本。
2. 上传新文件到服务器。
3. 使用 `Ctrl + F5` 强制刷新。
4. 清理服务器或 CDN 缓存。

## 安全要求

- MySQL 密码只能保存在 `api/config.php`。
- `api/config.php` 不得提交 Git 或放入公开压缩包。
- 数据库用户只授予本项目数据库的必要权限。
- 必须使用 HTTPS。
- 生产环境关闭 PHP `display_errors`，错误写入服务器日志。
- 定期备份 MySQL 数据库和网页 JSON 数据。
- 不要允许公网直接访问 MySQL 3306 端口。
- 当前为单账号模式；如需员工账号隔离，必须重新设计用户权限和 API，不能只修改前端。

## 维护检查清单

- [ ] `api/config.php` 只存在服务器
- [ ] MySQL 版本为 5.7.43
- [ ] `mysql-schema-5.7.sql` 已执行
- [ ] `/api/index.php?action=health` 正常
- [ ] PHP Session Cookie 正常
- [ ] 登录、退出和首次注册正常
- [ ] 项目新增、编辑和删除正常
- [ ] 预设同步正常
- [ ] 未完成提醒正常
- [ ] JSON 备份已经下载
- [ ] MySQL 数据库备份已经完成
- [ ] JavaScript 和 PHP 语法检查通过
- [ ] 资源缓存版本号已更新
