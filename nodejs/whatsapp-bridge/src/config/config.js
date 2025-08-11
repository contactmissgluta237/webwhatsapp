require('dotenv').config({ path: process.env.BRIDGE_ENV_PATH || undefined });

const get = (key, fallback) => process.env[key] || fallback;

module.exports = {
  port: parseInt(get('PORT', '3000'), 10),
  apiToken: get('WHATSAPP_API_TOKEN', ''),
  laravelApiUrl: get('LARAVEL_API_URL', 'http://localhost:8000'),
  logLevel: get('LOG_LEVEL', 'info'),
};
