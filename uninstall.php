<?php
// includes
function warung_class_loader($n) {
    $theClass = dirname(__FILE__) . '/includes/' . $n . '.php';
    if (file_exists($theClass) && include_once($theClass)) {
        return TRUE;
    } else {
        trigger_error("The class '$class' or the file '$theClass' failed to spl_autoload  ", E_USER_WARNING);
        return FALSE;
    }
}

spl_autoload_register('warung_class_loader');

// If uninstall/delete not called from WordPress then exit
if(!defined( 'ABSPATH' ) &&!defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit ();

// Delete option from options table
delete_option(WarungOptions::$OPT_NAME);
//remove any additional options and custom tables
global $wpdb;
$table_name = $wpdb->prefix . "wrg_order";
//build our query to delete our custom table
$sql = "DROP TABLE " . $table_name . ";";
//execute the query deleting the table
$wpdb->query($sql);
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

$table_name = $wpdb->prefix . "wrg_order_items";
//build our query to delete our custom table
$sql = "DROP TABLE " . $table_name . ";";
//execute the query deleting the table
$wpdb->query($sql);
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);

$table_name = $wpdb->prefix . "wrg_order_shipping";
//build our query to delete our custom table
$sql = "DROP TABLE " . $table_name . ";";
//execute the query deleting the table
$wpdb->query($sql);
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
?>
