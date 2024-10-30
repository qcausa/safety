<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

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
define( 'AUTH_KEY',          '=Q)mKD{at_K(M7J>=LU*, --y;|NDcxCoU~zAJLo!}8^YAZt.|%$_NImvE@.2o*v' );
define( 'SECURE_AUTH_KEY',   '&wlkx-@ZQDE1Mj}0@JhG58D$(QivqlVs<_G>R04S?W];rdmJ~_l~z?o`P|]TIQ}P' );
define( 'LOGGED_IN_KEY',     '^x_>E&Z$85<C~k4JDf,_7Ga3d3TGl&JQJZ0JwUoVy>@:FkO(8KM<&%[^batI{ _>' );
define( 'NONCE_KEY',         '2pVCqJWI@R~.LFKtEX-?5S_BfGjjOBB&^`H}%`v&&}Gtu~0D!/2kkS?1?=b`!6KU' );
define( 'AUTH_SALT',         ';YS)T;E~@j}xK,~1>y}Pz95MDT*xL#w,-UTQF7,F<WT3F1|`fK`OqK2^p_?TD?wI' );
define( 'SECURE_AUTH_SALT',  'pcYTq^QP6YDQsvq[RIhRp.g#eK1Gf~oaXc=&@pUoycgza*]X+l;u[!:rQp:178fr' );
define( 'LOGGED_IN_SALT',    'vSIQMj!Dy,6ug*Lvcm^,}WV?(#qyMwwr({J0n}>9LzlPotPk@dQ2daaA^2sQ?hss' );
define( 'NONCE_SALT',        'rvB.s/m%~%/)}4>QMHD]EG%6IlrW/p!(K0ICt HmN$YGo%7mtITOJ]4Kfr%ip`Qj' );
define( 'WP_CACHE_KEY_SALT', 'c6V/~TU;;B{(`6v8 |1?DIbX<BO#Bwdg5f,[qY6yF~I$sp^,LW{nLNWEH:A~U)im' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
