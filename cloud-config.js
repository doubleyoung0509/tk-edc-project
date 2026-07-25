// 现有 GitHub Pages 在迁移期间继续使用 Supabase。
// 上传到自有服务器域名后，系统会自动切换到同目录的 PHP/MySQL API。
const useLegacySupabase =
  location.hostname === 'doubleyoung0509.github.io' ||
  location.protocol === 'file:';

window.CLOUD_CONFIG = useLegacySupabase
  ? {
      provider: 'supabase',
      supabaseUrl: 'https://ujqprtrpwsfvcmrtvxoc.supabase.co',
      supabasePublishableKey: 'sb_publishable_yB8HDN2i8nN6hxxkpDw_Og_iSartgbm'
    }
  : {
      provider: 'mysql',
      apiBaseUrl: './api/index.php'
    };
