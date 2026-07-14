-- TK-EDC 多人权限：管理员可查看全部项目，员工只能查看自己创建的项目
-- 请先执行本文件，再在最后一行把老板账号 UUID 加入 team_members。

create table if not exists public.team_members (
  user_id uuid primary key references auth.users(id) on delete cascade,
  role text not null default 'employee' check (role in ('admin','employee')),
  display_name text not null default '',
  created_at timestamptz not null default now()
);

alter table public.projects add column if not exists created_by uuid references auth.users(id);
create index if not exists projects_created_by_idx on public.projects(created_by);

alter table public.team_members enable row level security;

create or replace function public.is_project_admin()
returns boolean
language sql
security definer
set search_path = public
as $$
  select exists (
    select 1 from public.team_members
    where user_id = auth.uid() and role = 'admin'
  );
$$;

revoke all on function public.is_project_admin() from public;
grant execute on function public.is_project_admin() to authenticated;

drop policy if exists "authenticated users manage projects" on public.projects;
drop policy if exists "team members read own profile" on public.team_members;
drop policy if exists "admins read all profiles" on public.team_members;
drop policy if exists "team members read allowed projects" on public.projects;
drop policy if exists "team members insert allowed projects" on public.projects;
drop policy if exists "team members update allowed projects" on public.projects;
drop policy if exists "team members delete allowed projects" on public.projects;

create policy "team members read own profile"
on public.team_members for select to authenticated
using (user_id = auth.uid());

create policy "admins read all profiles"
on public.team_members for select to authenticated
using (public.is_project_admin());

create policy "team members read allowed projects"
on public.projects for select to authenticated
using (public.is_project_admin() or created_by = auth.uid());

create policy "team members insert allowed projects"
on public.projects for insert to authenticated
with check (public.is_project_admin() or created_by = auth.uid());

create policy "team members update allowed projects"
on public.projects for update to authenticated
using (public.is_project_admin() or created_by = auth.uid())
with check (public.is_project_admin() or created_by = auth.uid());

create policy "team members delete allowed projects"
on public.projects for delete to authenticated
using (public.is_project_admin() or created_by = auth.uid());

-- 预设可供所有员工读取，但只有管理员可以新增、修改、删除。
drop policy if exists "authenticated users manage presets" on public.presets;
drop policy if exists "team members read presets" on public.presets;
drop policy if exists "admins write presets" on public.presets;

create policy "team members read presets"
on public.presets for select to authenticated using (true);

create policy "admins write presets"
on public.presets for all to authenticated
using (public.is_project_admin()) with check (public.is_project_admin());

grant select on public.team_members to authenticated;
grant select, insert, update, delete on public.projects to authenticated;
grant select, insert, update, delete on public.presets to authenticated;

-- 执行前请在 Supabase Authentication -> Users 复制老板账号 UUID，然后执行：
-- insert into public.team_members (user_id, role, display_name)
-- values ('老板账号UUID', 'admin', '老板')
-- on conflict (user_id) do update set role = 'admin';

