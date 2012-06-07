<?php
/*
Plugin Name: Warung 2.0
Plugin URI: http://warungsprei.com/
Description: Simple shop plugins for Indonesian stores.
Author: Hendra Setiawan
Version: 2.0
Author URI: http://id.linkedin.com/in/sihendra
*/

// version check
global $wp_version;

$exit_msg = 'Warung, require WordPress 2.6 or newer.
<a href="http://codex.wordpress.org/
Upgrading_WordPress">Please update!</a>';

if (version_compare($wp_version, "2.6", "<")) {
    exit($exit_msg);
}

// ## constants
DEFINE ('WARUNG_ROOT_URL', trailingslashit(WP_PLUGIN_URL . '/warung2.0'));

// ## includes
require_once ('lib/options.php');
require_once ('lib/util.php');

require_once ('lib/product.php');
require_once ('lib/cart.php');
require_once ('lib/shipping.php');
require_once ('lib/kasir.php');
require_once ('lib/user.php');
require_once ('lib/order.php');
require_once('lib/widget.php');

require_once ('lib/admin.php');
require_once ('lib/admin_order.php');
require_once ('lib/widget.php');
require_once ('lib/controller.php');



// ## init, controller
add_action('init', 'wrg_do_action');

// ## widget
add_action('widgets_init', create_function('', 'return register_widget("WarungCartWidget");'));

// ## css and JS
add_action('wp_print_scripts', 'wrg_init_scripts');
add_action('wp_print_styles', 'wrg_init_styles');

function wrg_init_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-form'); //, $this->pluginUrl.'scripts/jquery.form.js',array('jquery'));
    wp_enqueue_script('jquery_validaton', WARUNG_ROOT_URL . '/scripts/jquery.validate.js', array('jquery'));
    wp_enqueue_script('warung_js', WARUNG_ROOT_URL . '/scripts/warung.js', array('jquery'));
}

function wrg_init_styles() {
    wp_enqueue_style('warung_style', WARUNG_ROOT_URL . '/style/warung.css');
//    wp_enqueue_style('warung_style_bootstrap', WARUNG_ROOT_URL . '/style/bootstrap/css/bootstrap.css');
}

// ## admin menu/page
$warungAdmin = new WarungAdmin2();
add_action('admin_menu', array(&$warungAdmin,'admin_menu'));

// ##  plugins lifecycle 
register_activation_hook(__FILE__,'wrg_install');

function wrg_install() {
    $installed_ver = get_option("warung_db_version");

    // DB
    global $wpdb;
    
    //set the table structure version
    $warung_db_version = "2.0";
    
    // order table
    $table_name = $wpdb->prefix . "wrg_order";
    if ($installed_ver == "1.0") {
        // alter order
        $sqlAlter = 
        "ALTER TABLE `$table_name`   
        DROP COLUMN `dtpayment`, 
        DROP COLUMN `dtdelivery`, 
        CHANGE `status` `status_id` TINYINT(4) NOT NULL,
        CHANGE `dtlastupdated` `dtstatus` DATETIME NOT NULL,
        CHANGE `items_price` `total_price` INT(11) NOT NULL,
        CHANGE `shipping_weight` `total_weight` FLOAT NOT NULL;";

        $wpdb->query($sqlAlter);
    } 
    
    // create new
    $sql =
        "CREATE TABLE `$table_name` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `dtcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status_id` tintyint(4) NOT NULL,
        `dtstatus` datetime NOT NULL,
        `total_price` int(11) NOT NULL,
        `shipping_price` int(11) NOT NULL,
        `delivery_number` varchar(100) DEFAULT NULL,
        `total_weight` float NOT NULL,
        `shipping_services` varchar(50),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB;";
    
    
    // order items
    $table_name = $wpdb->prefix . "wrg_order_items";
    $sql .=
            "CREATE TABLE `$table_name` (
            `order_id` int(11) NOT NULL,
            `item_id` int(11) NOT NULL,
            `name` varchar(512) NOT NULL,
            `quantity` int(11) NOT NULL,
            `weight` float NOT NULL DEFAULT '0',
            `price` float NOT NULL DEFAULT '0',
            KEY `idx_wrg_order_items` (`order_id`)
        ) ENGINE=InnoDB;";

    // order shipping
    $table_name = $wpdb->prefix . "wrg_order_shipping";
    $sql .=
            "CREATE TABLE `$table_name` (
            `order_id` int(11) NOT NULL,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `mobile_phone` varchar(31) DEFAULT NULL,
            `phone` varchar(31) DEFAULT NULL,
            `address` varchar(200) DEFAULT NULL,
            `city` varchar(100) DEFAULT NULL,
            `state` varchar(100) DEFAULT NULL,
            `country` varchar(100) DEFAULT NULL,
            `additional_info` varchar(200) DEFAULT NULL,
            PRIMARY KEY (`order_id`)
        ) ENGINE=InnoDB;";
    
    // insert data

    // order status history
    $table_name = $wpdb->prefix . "wrg_order_status_history";
    $sql .= 
            "CREATE TABLE `$table_name` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` int(11) NOT NULL,
            `dtlog` datetime NOT NULL,
            `status_id` smallint(6) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_wrg_order_status_history` (`order_id`)
            ) ENGINE=InnoDB;";
        

    // shipping service
    $table_name = $wpdb->prefix . "wrg_shipping_service";
    $sql .= 
            "CREATE TABLE `$table_name` (
            `id` int(11) NOT NULL,
            `name` varchar(160) NOT NULL,
            `is_default` tinyint(4) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB;";
    
    // shipping service
    $table_name = $wpdb->prefix . "wrg_shipping_price";
    $sql .= 
            "CREATE TABLE `wp_wrg_shipping_price` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `service_id` smallint(6) NOT NULL,
            `origin` varchar(100) NOT NULL,
            `destination` varchar(100) NOT NULL,
            `price` int(11) NOT NULL,
            `delivery_time` smallint(6) NOT NULL,
            `min_weight` int(11) NOT NULL DEFAULT '1',
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB;";
    
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    //execute the query creating our table
    dbDelta($sql);
    //save the table structure version number
    add_option("warung_db_version", $warung_db_version);

    
    
}


?>
