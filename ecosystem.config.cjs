const path = require('node:path');

const appPath = process.env.APP_PATH || __dirname;
const phpBinary = process.env.PHP_BINARY || 'php';
const isWindows = process.platform === 'win32';

module.exports = {
    apps: [
        {
            name: 'facturador-queue',
            cwd: path.resolve(appPath),
            script: isWindows ? 'cmd.exe' : phpBinary,
            args: isWindows
                ? `/d /s /c "${phpBinary}" artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=180 --max-time=3600`
                : 'artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=180 --max-time=3600',
            interpreter: 'none',
            autorestart: true,
            exp_backoff_restart_delay: 1000,
            max_memory_restart: '256M',
            time: true,
            merge_logs: true,
        },
    ],
};
