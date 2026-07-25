# TK-EDC 每日项目记录

一个以电脑端为主的项目记录、客户资料、经营图表和财务报表系统。项目采用原生 HTML、CSS、JavaScript 开发，无构建步骤；数据可保存在浏览器本地，也可通过 Supabase 登录后同步到云端。

- 线上地址：<https://doubleyoung0509.github.io/tk-edc-project/>
- 代码仓库：<https://github.com/doubleyoung0509/tk-edc-project>
- 当前模式：单账号、同一账号多设备同步

## 主要功能

- 项目新增、编辑、删除、搜索、筛选、排序和分组
- 项目分类、项目名称、负责人、阶段状态和收款状态预设
- 金额、成本、自动利润、客户信息、备注和资料记录
- 不同项目及分类使用不同颜色区分
- 未完成项目自动提醒；每次打开网页提醒一次
- 隐藏已交付项目、批量修改、批量删除、显示或隐藏栏目
- 表格行高、列宽和手动列宽调整
- 客户联系方式及关联项目记录，只读查看项目详情
- 项目数据图表、分类分布、交付率和项目金额排行
- 每日、每周、每月财务报表及待收款提醒
- 本地自动备份、手动备份、JSON 导入导出
- HTML 报表及 SQLite 数据库脚本导出
- Supabase 登录、项目数据和预设云端同步

## 目录结构

```text
project-progress-web/
├─ index.html                    # 页面结构、弹窗和资源入口
├─ styles.css                    # 主界面、表格、图表和财务页面样式
├─ enhancements.css             # 提醒、备份、筛选等增强功能样式
├─ app.js                       # 项目核心数据、渲染、表单和云端同步
├─ enhancements.js              # 提醒、备份、批量操作和高级筛选
├─ cloud-config.js              # 当前 Supabase 公共连接配置
├─ cloud-config.example.js      # Supabase 配置模板
├─ supabase-schema.sql          # Supabase 建表、RLS 和权限策略
├─ supabase-migration-v2.sql    # 数据库兼容说明
├─ ONLINE-SETUP.md              # 简要在线部署说明
├─ vercel.json                  # Vercel 静态部署及安全响应头
├─ README.md                    # 项目维护文档
└─ .github/
   └─ workflows/
      └─ pages.yml              # GitHub Pages 自动部署流程
```

`.git/` 是本地 Git 历史，不属于运行源码，发布或交付压缩包时可以排除。

## 技术结构

```text
浏览器
├─ index.html
├─ styles.css + enhancements.css
├─ app.js
│  ├─ 项目数据和预设
│  ├─ 项目表格、客户、图表和财务页面
│  └─ Supabase 登录与同步
└─ enhancements.js
   ├─ 未完成提醒
   ├─ 快捷筛选、批量操作和数据检查
   └─ 备份、恢复和导航统计
        │
        ├─ localStorage（本地缓存与偏好）
        └─ Supabase（云端项目和预设）
```

项目没有 npm 依赖、打包器或后端服务。Supabase JavaScript SDK 通过 CDN 在 `index.html` 中加载。

## 本地运行

最简单的方式是直接双击 `index.html`。

更推荐使用本地静态服务器，避免浏览器对 `file://` 页面进行额外限制：

```powershell
cd project-progress-web
python -m http.server 8080
```

然后访问：

```text
http://localhost:8080
```

如果没有配置 Supabase，系统会进入本地模式，数据保存在当前浏览器中。

## Supabase 配置

1. 创建 Supabase 项目。
2. 在 Supabase SQL Editor 执行 `supabase-schema.sql`。
3. 在 Authentication 中创建或注册登录账号。
4. 复制 `cloud-config.example.js` 为 `cloud-config.js`。
5. 填入项目 URL 和 Publishable Key。

```js
window.CLOUD_CONFIG = {
  supabaseUrl: 'https://YOUR_PROJECT.supabase.co',
  supabasePublishableKey: 'YOUR_PUBLISHABLE_KEY'
};
```

Publishable Key 可以放在浏览器前端，但必须配合 RLS。绝对不要把 `service_role` Key、数据库密码或其他管理员密钥写入网页或提交到 GitHub。

### 数据库表

`projects` 保存项目记录，主要字段如下：

| 字段 | 说明 |
| --- | --- |
| `id` | 项目编号，主键 |
| `category` | 项目分类 |
| `project_name` | 项目名称 |
| `amount` | 项目金额 |
| `cost` | 项目成本 |
| `start_date` | 开始时间 |
| `owner` | 负责人 |
| `client` | 客户信息 |
| `statuses` | 阶段状态 JSON 数组 |
| `files` | 资料及兼容元数据 |
| `note` | 备注 |
| `created_at` | 创建时间 |
| `updated_at` | 更新时间 |

`presets` 保存项目分类、项目名称、负责人、阶段状态、收款状态和云端备份。

### 收款状态兼容机制

当前 Supabase 表没有单独的 `payment_status` 字段。`app.js` 使用 `__TKEDC_META__:` 前缀，将收款状态和资料一起编码进 `projects.files`：

- `encodeCloudFiles()`：写入云端前编码
- `decodeCloudFiles()`：读取云端后解码
- `projectToCloud()`：前端记录转换为数据库格式
- `projectFromCloud()`：数据库记录转换为前端格式

维护云端同步代码时，不要绕过这些函数，否则可能导致收款状态丢失。

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

清除浏览器网站数据会删除本地记录和偏好。执行前应先在网页“备份与恢复”中导出完整 JSON 备份。

## 部署

### GitHub Pages

仓库已经包含 `.github/workflows/pages.yml`。推送到 `main` 分支后，GitHub Actions 会自动发布整个目录。

维护流程：

```powershell
git add .
git commit -m "Describe the change"
git push origin main
```

在 GitHub 仓库的 **Settings → Pages** 中，应使用 GitHub Actions 作为发布来源。

### Vercel

仓库已经包含 `vercel.json`，直接在 Vercel 导入仓库即可作为静态网站部署。

### 自有服务器

将本目录中除 `.git/` 外的文件上传到网站根目录，例如：

```text
/var/www/tk-edc/
```

Nginx 示例：

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/tk-edc;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

配置 HTTPS 后，将域名解析到服务器并使用 Certbot 或服务器面板申请 SSL 证书。

## 修改代码后的缓存规则

`index.html` 使用查询参数控制浏览器缓存，例如：

```html
<link rel="stylesheet" href="enhancements.css?v=20260730">
<script src="enhancements.js?v=20260731"></script>
```

修改某个 CSS 或 JavaScript 文件后，必须同步增加该文件的 `v=` 版本号，否则线上用户可能仍看到旧页面。

建议按以下顺序发布：

1. 先导出数据备份。
2. 修改源文件。
3. 修改 `index.html` 中对应资源版本号。
4. 执行 JavaScript 语法检查。
5. 本地打开页面检查新增、编辑、删除和同步。
6. 提交并推送到 `main`。
7. 访问线上地址并强制刷新验证。

语法检查：

```powershell
node --check app.js
node --check enhancements.js
```

## 数据备份与恢复

网页中提供以下方式：

- 自动备份：修改记录前自动保存最近版本
- 手动备份：保存到本地；登录后同时尝试保存到云端
- 导出完整备份：下载 JSON 文件
- 导入备份：从 JSON 恢复全部记录
- 导出 HTML：生成可独立查看的项目报表
- 导出数据库：生成 SQLite SQL 脚本

建议每周至少下载一次 JSON 完整备份，并将压缩包和数据备份分开保存。

## 常见问题

### 网页打不开或空白

1. 检查 `index.html`、CSS 和 JavaScript 文件是否位于同一目录。
2. 打开浏览器开发者工具的 Console 查看错误。
3. 检查 Supabase SDK CDN 是否可以访问。
4. 强制刷新页面：`Ctrl + F5`。
5. 检查 `index.html` 中的资源版本号是否已更新。

### 显示“仅本地模式”

检查 `cloud-config.js` 是否存在，以及 URL 和 Publishable Key 是否正确。

### 显示“数据库未就绪”

在 Supabase SQL Editor 重新检查并执行 `supabase-schema.sql`，确认 `projects`、`presets` 表和 RLS 策略存在。

### 显示“云端同步失败”

1. 确认已经登录。
2. 检查网络和 Supabase 项目状态。
3. 检查 RLS 策略及 authenticated 权限。
4. 不要连续重复修改，先导出本地 JSON 备份。
5. 打开 Console 查看 Supabase 返回的具体错误。

### 修改后线上没有变化

1. 增加 `index.html` 中对应资源的 `v=` 版本。
2. 确认 GitHub Actions 部署成功。
3. 使用 `Ctrl + F5` 强制刷新。
4. 在网址后增加临时版本参数，例如 `?v=20260731`。

## 维护注意事项

- `app.js` 是核心逻辑；改动前先备份数据。
- `enhancements.js` 依赖 `app.js` 中的全局函数，加载顺序不能互换。
- `styles.css` 是基础样式，`enhancements.css` 必须在它之后加载。
- 项目编号 `id` 是 Supabase 主键，修改编号时会删除旧编号的云端记录。
- 客户资料由项目中的 `client` 字段自动整理，不是单独的客户数据库表。
- “交付完成”状态用于判断已完成项目和未完成提醒。
- 当前 RLS 允许任意已登录账号访问全部数据，适合单账号模式，不适合员工数据隔离。
- 如果未来恢复多人权限，应先给表增加 `user_id` 或 `owner_id`，再重新设计 RLS，不能只修改前端。

## 版本交接检查

- [ ] 源码压缩包可以正常解压
- [ ] `README.md`、SQL 文件和部署配置包含在压缩包中
- [ ] `cloud-config.js` 只包含 Publishable Key
- [ ] `node --check app.js` 通过
- [ ] `node --check enhancements.js` 通过
- [ ] 本地新增和编辑项目正常
- [ ] 未完成项目提醒正常
- [ ] Supabase 登录和同步正常
- [ ] JSON 备份已经单独保存
- [ ] 线上版本已强制刷新验证
