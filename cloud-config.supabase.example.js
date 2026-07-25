// 旧 Supabase 模式仅用于迁移回退。
// Publishable Key 可以用于浏览器；绝对不要把 service_role key 放进网页。
window.CLOUD_CONFIG = {
  provider: 'supabase',
  supabaseUrl: 'https://YOUR_PROJECT.supabase.co',
  supabasePublishableKey: 'YOUR_PUBLISHABLE_KEY'
};
