# 在线部署准备

1. 创建 Supabase 项目。
2. 在 SQL Editor 执行 `supabase-schema.sql`。
3. 在 Authentication 中创建登录账号。
4. 复制 `cloud-config.example.js` 为 `cloud-config.js`，填写 Project URL 与 Publishable Key。
5. 将项目上传至 GitHub，并在 Vercel 中导入该仓库。

安全要求：不要把 Supabase `service_role` key 写入任何前端文件。
