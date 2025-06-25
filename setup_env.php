<?php

// Set up environment variables for testing
putenv('APP_NAME="Service Learning Management"');
putenv('APP_ENV=local');
putenv('APP_KEY=base64:'.base64_encode(random_bytes(32)));
putenv('APP_DEBUG=true');
putenv('APP_URL=http://localhost');

putenv('LOG_CHANNEL=stack');
putenv('LOG_DEPRECATIONS_CHANNEL=null');
putenv('LOG_LEVEL=debug');

putenv('DB_CONNECTION=sqlite');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=3306');
putenv('DB_DATABASE=database/database.sqlite');
putenv('DB_USERNAME=root');
putenv('DB_PASSWORD=');

putenv('BROADCAST_DRIVER=log');
putenv('CACHE_DRIVER=file');
putenv('FILESYSTEM_DISK=local');
putenv('QUEUE_CONNECTION=sync');
putenv('SESSION_DRIVER=file');
putenv('SESSION_LIFETIME=120');

putenv('MEMCACHED_HOST=127.0.0.1');

putenv('REDIS_HOST=127.0.0.1');
putenv('REDIS_PASSWORD=null');
putenv('REDIS_PORT=6379');
putenv('REDIS_DB=0');

putenv('MAIL_MAILER=smtp');
putenv('MAIL_HOST=mailpit');
putenv('MAIL_PORT=1025');
putenv('MAIL_USERNAME=null');
putenv('MAIL_PASSWORD=null');
putenv('MAIL_ENCRYPTION=null');
putenv('MAIL_FROM_ADDRESS="hello@example.com"');
putenv('MAIL_FROM_NAME="Service Learning Management"');

echo "Environment variables set up successfully!\n";
echo "APP_KEY: " . getenv('APP_KEY') . "\n";
echo "DB_DATABASE: " . getenv('DB_DATABASE') . "\n";
echo "CACHE_DRIVER: " . getenv('CACHE_DRIVER') . "\n";
echo "QUEUE_CONNECTION: " . getenv('QUEUE_CONNECTION') . "\n"; 