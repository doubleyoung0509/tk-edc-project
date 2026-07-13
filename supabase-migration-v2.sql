-- TK-EDC 项目管理增强栏目升级
-- 在 Supabase Dashboard → SQL Editor 中完整执行一次

alter table public.projects add column if not exists deadline date;
alter table public.projects add column if not exists priority text not null default '普通';
alter table public.projects add column if not exists customer_source text not null default '';
alter table public.projects add column if not exists contact text not null default '';
alter table public.projects add column if not exists payment_status text not null default '未收款';
alter table public.projects add column if not exists delivery_progress integer not null default 0;
alter table public.projects add column if not exists tags text not null default '';
alter table public.projects add column if not exists follow_up text not null default '';

update public.projects
set priority = coalesce(nullif(priority, ''), '普通'),
    payment_status = coalesce(nullif(payment_status, ''), '未收款'),
    delivery_progress = case
      when statuses ? '交付完成' then 100
      else greatest(0, least(100, coalesce(delivery_progress, 0)))
    end;
