<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'shanellestore' );

/** Database username */
define( 'DB_USER', 'shanellestore' );

/** Database password */
define( 'DB_PASSWORD', 'm2GXNEDeYFZwx3z9' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'g$`DMHzbf8JdmaJnGGPH<D]P,#R#vJ0NxN?JA^/BoX9T#Umk@kItl)VV@s3-1GF5' );
define( 'SECURE_AUTH_KEY',  'yL<ZioI5%|g]cX{p5Y1Zn`l[~VufaN)CtA^<vy/k# X4+T=/SWZJ[@73(D$igl8P' );
define( 'LOGGED_IN_KEY',    'M%EncB[TJ-Xnu5gq2^Q(|5,Axb@SF]<nk^Saxe!OdDQL*;Vp^OT/DTF$KiSO=wZM' );
define( 'NONCE_KEY',        '|0zXy3vwu~%x4n()A< {j()sjh0vSUgVfA)a-3EZ9(fgiZD3IL$MulENM&{p8mL}' );
define( 'AUTH_SALT',        '0zvJ^ckvu8KGDg+v6V2e+7xQ3VxO;%z&6uB,2.&=aUPz@OnR?MBbA5M6oiz!Wo&X' );
define( 'SECURE_AUTH_SALT', '2wzQFYqRNhWyKHzQj*Am*(f8^(/G[tzRrG4{8c4+VHy$47AH?#s;(93lg>bzr~o[' );
define( 'LOGGED_IN_SALT',   'y6muZQMSX[En/x*?5g$3Q>p8bt3e;6=pYTp4TY#ogm+h>,LN@xV4l]la&s?>~z_G' );
define( 'NONCE_SALT',       '|-AfY?-s.O|jFmxD|/ 8Ai_M&I}|g3~8AHTWx2.WjkKSK`zV_itik|8}AM;I?J+`' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

@ini_set('display_errors', 0);
/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
