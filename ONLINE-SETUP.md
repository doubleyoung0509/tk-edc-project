# 在线部署准备

1. 创建 Supabase 项目。
2. 在 SQL Editor 执行 `supabase-schema.sql`。
3. 在 Authentication 中创建登录账号。
4. 复制 `cloud-config.example.js` 为 `cloud-config.js`，填写 Project URL 与 Publishable Key。
5. 将项目上传至 GitHub，并在 Vercel 中导入该仓库。

安全要求：不要把 Supabase `service_role` key 写入任何前端文件。

## 多人权限

执行 `supabase-team-permissions.sql` 后，在 Supabase Authentication → Users 中复制账号 UUID：

```sql
insert into public.team_members (user_id, role, display_name)
values ('老板账号UUID', 'admin', '老板')
on conflict (user_id) do update set role = 'admin';
```

员工账号同样加入 `team_members`，但角色使用 `employee`。管理员可以看到全部项目；员工只能看到自己创建的项目。员工没有预设管理权限，客户资料页也保持只读。
