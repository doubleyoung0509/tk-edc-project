-- TK-EDC 项目管理增强栏目升级
-- 在 Supabase Dashboard → SQL Editor 中完整执行一次

alter table public.projects add column if not exists priority text not null default '普通';
alter table public.projects add column if not exists payment_status text not null default '未收款';

update public.projects
set priority = coalesce(nullif(priority, ''), '普通'),
    payment_status = coalesce(nullif(payment_status, ''), '未收款');
