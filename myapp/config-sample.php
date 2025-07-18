<?php
// MySql database host
define('DB_HOST', 'localhost');

// MySql database username
define('DB_USER', 'username');

// MySql database password
define('DB_PASSWORD', 'password');

// MySql database name
define('DB_NAME', 'database_name');

// MySql database table prefix
define('DB_TABLE_PREFIX', 'oc_');

// Relative web url
define('REL_WEB_URL', 'rel_here');

// Web address - modify here for SSL version of site
define('WEB_PATH', 'http://localhost');


// *************************************** //
// ** OPTIONAL CONFIGURATION PARAMETERS ** //
// *************************************** //

// Enable debugging
// define('OSC_DEBUG', true);             // show PHP error logs and notices
// define('OSC_DEBUG_DB', true);          // show DB queries
// define('OSC_DEBUG_LOG', true);         // save PHP errors & logs to oc-content/debug.log
// define('OSC_DEBUG_DB_LOG', true);      // save DB logs into oc-content/queries.log
// define('OSC_DEBUG_DB_EXPLAIN', true);  // save DB explain logs into oc-content/explain_queries.log
// define('OSC_DEBUG_CACHE', true);       // show cache debug information, when cache is enabled


// Change backoffice folder (after re-naming /oc-admin/ folder)
// define('OC_ADMIN_FOLDER', 'oc-admin');


// Demo mode
//define('DEMO', true);
//define('DEMO_THEMES', true);
//define('DEMO_PLUGINS', true);


// PHP memory limit (ideally should be more than 128MB)
// define('OSC_MEMORY_LIMIT', '256M');

// Debug PHP mailer. OSC_DEBUG must be true. Error logs from PHPMailer goes to oc-content/debug.log automatically.
// Accepted debug level values:  1 - client only, 2 - client and server (default), 3 - client, server and connection, 4 - low-level information
// define('PHPMAILER_DEBUG_LEVEL', 1);

// Set cookies domain to transfer cookies & session across domain & subdomains. Enter 'yourdomain.com' to transfer cookies to subdomains.
// After setting COOKIE_DOMAIN, clean cache, cookies and restart browser!
// define('COOKIE_DOMAIN', 'yoursite.com');


// Cache options for OSC_CACHE: memcache, memcached, apc, apcu, default
// Default cache means dummy one - just imitates cache
// define('OSC_CACHE_TTL', 60);   // Cache refresh time in seconds

// MemCache caching option (database queries cache). Select only one $_cache_config option, TCP or Unix socket
// define('OSC_CACHE', 'memcache');
// $_cache_config[] = array('default_host' => '127.0.0.1', 'default_port' => 11211, 'default_weight' => 1);  // TCP option
// $_cache_config[] = array('default_host' => '/usr/local/var/run/memcache.sock', 'default_port' => 0, 'default_weight' => 1);  // Unix socket option

// MemCached caching option (database queries cache). Select only one $_cache_config option, TCP or Unix socket
// define('OSC_CACHE', 'memcached');
// $_cache_config[] = array('default_host' => '127.0.0.1', 'default_port' => 11211, 'default_weight' => 1);  // TCP option
// $_cache_config[] = array('default_host' => '/usr/local/var/run/memcached.sock', 'default_port' => 0, 'default_weight' => 1);  // Unix socket option

// Redis caching option (database queries cache). Only one $_cache_config option supported, TCP or Unix socket
// define('OSC_CACHE', 'redis');
// $_cache_config[] = array('default_host' => '127.0.0.1', 'default_port' => 6379, 'default_password' => '');  // TCP option
// $_cache_config[] = array('default_host' => '/usr/local/var/run/redis.sock', 'default_port' => -1, 'default_password' => '');  // Unix socket option


// Force disable URL encoding for non-latin characters
// define('OSC_FORCE_DISABLE_URL_ENCODING', true);

// Alpha & beta testing - experimental
// define('ALPHA_TEST', true);
// define('BETA_TEST', true);

// Increase default login time for user
// session_set_cookie_params(2592000);
// ini_set('session.gc_maxlifetime', 2592000);

?>