-- 在 Supabase Dashboard → SQL Editor 中执行
create table if not exists public.projects (
  id text primary key,
  category text not null default '',
  project_name text not null default '',
  amount numeric not null default 0,
  cost numeric not null default 0,
  start_date date,
  priority text not null default '普通',
  owner text not null default '',
  client text not null default '',
  payment_status text not null default '未收款',
  statuses jsonb not null default '[]'::jsonb,
  files text not null default '',
  note text not null default '',
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

-- 兼容已经建好的旧数据库，重复执行不会报错
alter table public.projects add column if not exists priority text not null default '普通';
alter table public.projects add column if not exists payment_status text not null default '未收款';

create table if not exists public.presets (
  preset_type text not null,
  preset_value text not null,
  created_at timestamptz not null default now(),
  primary key (preset_type, preset_value)
);

alter table public.projects enable row level security;
alter table public.presets enable row level security;

create policy "authenticated users manage projects"
on public.projects for all to authenticated
using (true) with check (true);

create policy "authenticated users manage presets"
on public.presets for all to authenticated
using (true) with check (true);

grant select, insert, update, delete on public.projects to authenticated;
grant select, insert, update, delete on public.presets to authenticated;
