<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */
// Required for batcache use
define('WP_CACHE', true);

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */

if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'],'Google App Engine') !== false) {
	define('DB_NAME', 'fredautoparts_db');
	/** Live environment Cloud SQL login and SITE_URL info */
	/** Note that from App Engine, the password is not required, so leave it blank here */
	// define('DB_HOST', ':/cloudsql/tyo-lab-databases:hosting1');
	
	// /** MySQL database username */
	// define('DB_USER', 'fap_root');
	
	// /** MySQL database password */
	// define('DB_PASSWORD', '1qaz@WSX');

	define('DB_HOST', ':/cloudsql/fred-auto-parts:hosting1');
	
	/** MySQL database username */
	define('DB_USER', 'root');
	
	/** MySQL database password */
	define('DB_PASSWORD', '');

	/** IF RUN ON GOOGLE APP ENGINE */
	define('WP_HOSTING_APP_ENGINE', true);

	/**
	 * WordPress Database Table prefix.
	 *
	 * You can have multiple installations in one database if you give each a unique
	 * prefix. Only numbers, letters, and underscores please!
	 */
	$table_prefix  = 'fap_';

} else {
	/** Local environment MySQL login info */
	// 	define('DB_HOST', '173.194.226.139');
	define('DB_NAME', 'fap_store_db');

	/** MySQL database username */
	define('DB_USER', 'root');
	
	/** MySQL database password */
	define('DB_PASSWORD', '');
	
	/** MySQL hostname */
	define('DB_HOST', 'localhost');

	$table_prefix  = 'wp_';

	/** IF RUN ON GOOGLE APP ENGINE */
	define('WP_HOSTING_APP_ENGINE', false);

	/**
	 * WordPress Database Table prefix.
	 *
	 * You can have multiple installations in one database if you give each a unique
	 * prefix. Only numbers, letters, and underscores please!
	 */
	// define('DB_NAME', 'fredautoparts_db');
	
	// /** MySQL database username */
	// define('DB_USER', 'eric');
	
	// /** MySQL database password */
	// define('DB_PASSWORD', 'rtyu9908#');
	
	// /** MySQL hostname */
	// define('DB_HOST', '173.194.85.92');

	// $table_prefix  = 'fap_';
// 	define('DB_USER', 'root');
// 	define('DB_PASSWORD', '');
}

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'uaaq#JboHFEeyoxKU?;Gn7`IVy,exVE]syfc93mmji+:zW{EjGYVpRD`ejG^&H;h');
define('SECURE_AUTH_KEY',  'jXMeZ6u`F.[-Bf?MBMneqX]6)/Xd</EUs6B)jT}eJ[+dGz/7Dc4o;w:JL;6 1=nX');
define('LOGGED_IN_KEY',    'gd4ZuV2JZQwuTAiI?{xmX.*d)~J#_=C2E|)U8JR}`db|}zKoB6M<:#l{-JQ{pdmR');
define('NONCE_KEY',        '$@RNjy;%uVl=xy`P4)>{RM1rjm+_ORtcSGlz7l010Mk)Rgz9hw}akfvL7_PWz46 ');
define('AUTH_SALT',        'w;4=b* NZ*1{5`L{mCOp:TbRZJHA.|v{Rz Mj 7JWjOk[kzV 7 &.G(A%K|]u[2g');
define('SECURE_AUTH_SALT', '=w5E/2vg8XT^cuu.ZIY/Mrae>K&@O|kYPHi_$PbI$XP==P5}jK_]}.7xhh#)03D%');
define('LOGGED_IN_SALT',   '_E?57uWZ7*=vmC3(&~oHsi,,(kJ7WoJbTpr[H-PqUf_ua-|}@Y@|FN]Tmt}?h$WJ');
define('NONCE_SALT',       ':?=mAKSCo,:Av|%[;C_i,Dszy)p65<)0QA2O5N;?okcW`EX3QddSB7#/4ulJ>w,+');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_fap_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the chosen
 * language must be installed to wp-content/languages. For example, install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable German
 * language support.
 */
// define('WPLANG', '');

if (WP_HOSTING_APP_ENGINE) {
	$host = $using_protocol . $_SERVER['SERVER_NAME'];
	$usingSSL = (strpos($url,'appspot.com') !== false) ? true : false;
	$using_protocol = 'http' . ($usingSSL ? 's' : '');

	$port = $_SERVER['SERVER_PORT'];

	$url = $using_protocol . '://' . $host . (($port != 80) ? (':' . $port) : '');

	define( 'FORCE_SSL_ADMIN', $usingSSL );

	define( 'FORCE_SSL_LOGIN', $usingSSL );

	// define( 'WP_SITEURL', $url);
	// define( 'WP_HOME', $url);

	define( 'WP_SITEURL', "https://fred-auto-parts.appspot.com");
	define( 'WP_HOME', "https://fred-auto-parts.appspot.com");

	define('WP_DEBUG', false);
}
else {
	define( 'WP_SITEURL', "http://localhost:8080");
	define( 'WP_HOME', "http://localhost:8080");

	/**
	 * For developers: WordPress debugging mode.
	 *
	 * Change this to true to enable the display of notices during development.
	 * It is strongly recommended that plugin and theme developers use WP_DEBUG
	 * in their development environments.
	 */
	ini_set('log_errors','On');
	
	ini_set('display_errors','Off');
	
	ini_set('error_reporting', E_ALL );
	
	define('WP_DEBUG', false);
	
	define('WP_DEBUG_LOG', true);
	
	define('WP_DEBUG_DISPLAY', false);
}

/**
 * Disable default wp-cron in favor of a real cron job
 */
define('DISABLE_WP_CRON', true);

// configures batcache
$batcache = [
		'seconds'=>0,
		'max_age'=>30*60, // 30 minutes
		'debug'=>false
];

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
