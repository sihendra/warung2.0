<?php

class WarungAdmin2 {

    function admin_menu() {
        add_menu_page('Warung Options', 'Warung 2.0', 'administrator', __FILE__, array(&$this, 'handle_options'), WARUNG_ROOT_URL . '/images/icon.png');
        // add sub menu
        add_submenu_page(__FILE__, 'Warung General Options', 'General', 'administrator', __FILE__, array(&$this, 'handle_options'));
        add_submenu_page(__FILE__, 'Warung Shipping', 'Shipping', 'administrator', __FILE__ . '_shipping', array(&$this, 'handle_shipping'));
        add_submenu_page(__FILE__, 'Warung Product Options', 'Product Options', 'administrator', __FILE__ . '_product_option', array(&$this, 'handle_product_opt'));


        $orderAdmin = new WarungAdminOrder();
        add_submenu_page(__FILE__, 'Warung Orders', 'Orders', 'administrator', dirname(__FILE__) . '/WarungAdminOrder.php', array(&$orderAdmin, 'handle_orders'));

        // add metabox in edit post page
        add_meta_box('warung-product-id', 'Product Information', array(&$this, 'display_product_options'), 'post', 'normal', 'high');

        // save hook
        add_action('save_post', array(&$this, 'save_product_details'));

        // add default action
        add_action('warung_shipping_options', array($this, 'handle_byweight_shipping'));
        add_action('warung_display_product_options', array($this, 'displayProductOptionsWithDiscount'));
    }

    function handle_options() {

        ob_start();

        $opt = new WarungOptions();
        $options = $opt->getOptions();

        if (isset($_POST['general_submit'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                if (empty($options) || !is_array($options)) {
                    $options = array();
                }

                $options['currency'] = $_POST['currency'];
                $options['add_to_cart'] = $_POST['add_to_cart'];
                $options['checkout_page'] = $_POST['checkout_page'];
                $options['shipping_sim_page'] = $_POST['shipping_sim_page'];
                $options['weight_sign'] = $_POST['weight_sign'];
                $options['admin_page'] = $_POST['admin_page'];

                update_option(WarungOptions::$OPT_NAME, $options);

                echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
            } else {
                echo '<div class="updated fade"><p>Plugin Setting Not Saved caused by security breach.</p></div>';
            }
        }

        $currency = $options['currency'];
        $add2cart = $options['add_to_cart'];
        $checkout_page = $options['checkout_page'];
        $shipping_sim_page = $options['shipping_sim_page'];
        $weight_sign = $options['weight_sign'];
        $admin_page = $options['admin_page'];
        ?>
        <div class="wrap" style="max-width:950px !important;">
            <h2>General Options</h2>
            <div id="poststuff" style="margin-top:10px;">
                <div id="mainblock" style="width:810px">
                    <div class="dbx-content">
                        <form action="" method="post">
                            <?= wp_nonce_field('warung-nonce') ?>
                            <div class="form-field">
                                <label for="currency">Currency</label>
                                <input id="currency" type="text" size="5" name="currency" value="<?= stripslashes($currency) ?>"/>
                            </div>
                            <div class="form-field">
                                <label for="weight_sign">Weight Sign</label>
                                <input id="weight_sign" type="text" size="5" name="weight_sign" value="<?= stripslashes($weight_sign) ?>"/>
                            </div>
                            <div class="form-field">
                                <label for="add_to_cart">Add to cart text</label>
                                <input id="add_to_cart" type="text" size="10" name="add_to_cart" value="<?= stripslashes($add2cart) ?>"/>
                            </div>
                            <div class="form-field">
                                <label for="checkout_page">Checkout Page</label>
                                <select id="checkout_page" name="checkout_page">
                                    <?
                                    foreach (get_pages() as $p) {
                                        echo '<option value="' . $p->ID . '"' . ($checkout_page == $p->ID ? 'selected=selected' : '') . '>' . $p->post_title . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="shipping_sim_page">Shipping Sim Page</label>
                                <select id="shipping_sim_page" name="shipping_sim_page">
                                    <?
                                    if (empty($shipping_sim_page)) {
                                        echo '<option value="" selected="selected">-- Please Select --</option>';
                                    }
                                    foreach (get_pages() as $p) {
                                        echo '<option value="' . $p->ID . '"' . ($shipping_sim_page == $p->ID ? 'selected=selected' : '') . '>' . $p->post_title . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="admin_page">Admin Page</label>
                                <select id="admin_page" name="admin_page">
                                    <?
                                    if (empty($admin_page)) {
                                        echo '<option value="" selected="selected">-- Please Select --</option>';
                                    }
                                    foreach (get_pages() as $p) {
                                        echo '<option value="' . $p->ID . '"' . ($admin_page == $p->ID ? 'selected=selected' : '') . '>' . $p->post_title . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>



                            <div class="submit"><input type="submit" name="general_submit" value="Update" /></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?
        $out = ob_get_contents();
        ob_end_clean();

        echo $out;
    }

    function handle_product_opt() {
        ob_start();

        $opt = new WarungOptions();
        $options = $opt->getOptions();

        if (isset($_POST['product_opt_submit'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                if (empty($options) || !is_array($options)) {
                    $options = array();
                }
                $options['prod_options'] = WarungUtils::parseParametersToObject($_POST, 'prod_option');

                update_option(Warung::$db_option, $options);

                echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
            } else {
                echo '<div class="updated fade"><p>Plugin Setting Not Saved caused by security breach.</p></div>';
            }
        }

        $prod_options = $options['prod_options'];
        ?>
        <div class="wrap" style="max-width:950px !important;">
            <h2>Product Option Set</h2>
            <div id="poststuff" style="margin-top:10px;">
                <div id="mainblock" style="width:810px">
                    <div class="dbx-content">
                        <form action="" method="post">
                            <?= wp_nonce_field('warung-nonce') ?>
                            <?
                            $i = 0;
                            if (is_array($prod_options)) {
                                foreach ($prod_options as $key => $val) {
                                    $name = '';
                                    $prod = '';
                                    $txt = '';
                                    if (is_object($val)) {
                                        $name = $val->name;
                                        $prod = $val->value;
                                        if (isset($val->txt)) {
                                            $txt = $val->txt;
                                        }
                                    } else {
                                        // backward compatibility
                                        $name = $key;
                                        $prod = $val;
                                    }
                                    ?>
                                    <div class="form-field">
                                        <label for="prod_option_name-<?= $i ?>">Name</label>
                                        <input type="text" id="prod_option_name-<?= $i ?>" name="prod_option_name-<?= $i ?>" value="<?= stripslashes($name) ?>" />
                                    </div>
                                    <div class="form-field">
                                        <label for="prod_option_value-<?= $i ?>">Value</label>
                                        <textarea id="prod_option_value-<?= $i ?>" name="prod_option_value-<?= $i ?>" rows="5" cols="50"><?= stripslashes($prod) ?></textarea>
                                    </div>
                                    <div class="form-field">
                                        <label for="prod_option_txt-<?= $i ?>">Product Info</label>
                                        <textarea id="prod_option_txt-<?= $i ?>" name="prod_option_txt-<?= $i ?>" rows="5" cols="50"><?= stripslashes($txt) ?></textarea>
                                    </div>

                                    <?
                                    $i++;
                                }
                            }
                            ?>
                            <div class="form-field">
                                <label for="prod_option_name-<?= $i ?>">Name</label>
                                <input type="text" id="prod_option_name-<?= $i ?>" name="prod_option_name-<?= $i ?>" value="" />
                            </div>
                            <div class="form-field">
                                <label for="prod_option_value-<?= $i ?>">Value</label>
                                <textarea name="prod_option_value-<?= $i ?>" id="prod_option_value-<?= $i ?>" rows="5" cols="50"></textarea>
                            </div>
                            <div class="form-field">
                                <label for="prod_option_value-<?= $i ?>">Product Info</label>
                                <textarea name="prod_option_txt-<?= $i ?>" id="prod_option_txt-<?= $i ?>" rows="5" cols="50"></textarea>
                            </div>


                            <div class="submit"><input type="submit" name="product_opt_submit" value="Update" /></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?
        $out = ob_get_contents();
        ob_end_clean();

        echo $out;
    }

    function handle_byweight_shipping() {
        ob_start();

        $opt = new WarungOptions();
        $options = $opt->getOptions();

        if (isset($_POST['shipping_byweight_submit'])) {
            //check security
            if (check_admin_referer('warung-nonce')) {

                if (empty($options) || !is_array($options)) {
                    $options = array();
                }
                
                // save the shipping data
                $options['shipping_byweight'] = WarungUtils::parseParametersToObject($_POST, 'shipping');
                
                // save default shipping
                if ($_REQUEST["default_shipping"]) {
                    $options["default_shipping"] = $_REQUEST["default_shipping"];
                }

                
                update_option(WarungOptions::$OPT_NAME, $options);
                
                echo '<div class="updated fade"><p>Plugin Setting Saved.</p></div>';
            } else {
                echo '<div class="updated fade"><p>Plugin Setting Not Saved caused by security breach.</p></div>';
            }
        }

        $shipping_options = $options['shipping_byweight'];
        ?>
        <div class="wrap" style="max-width:950px !important;">
            <h2>Shipping Services By Weight</h2>
            <div id="poststuff" style="margin-top:10px;">
                <div id="mainblock" style="width:810px">
                    <div class="dbx-content">
                        <form action="" method="post">
                            <?= wp_nonce_field('warung-nonce') ?>
                            <div class="form-field">
                                <label for="default_shipping<?= $i ?>"><?php _e('Choose default shipping') ?></label>
                                <select id="default_shipping" name="default_shipping">
                                    <option value="">Default</option>
                                    <?
                                    $shippingServices = GenericShippingService::getAllServices();
                                    foreach ($shippingServices as $shipping) {
                                        if ($options["default_shipping"] == $shipping->id) {
                                            ?><option value="<?= $shipping->id ?>" selected="selected"><?= $shipping->name ?></option><?
                                        } else {
                                            ?><option value="<?= $shipping->id ?>"><?= $shipping->name ?></option><?
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <?
                            $i = 0;
                            if (is_array($shipping_options)) {
                                foreach ($shipping_options as $key => $val) {
                                    $name = '';
                                    $value = '';
                                    $priority = '';
                                    $totalWeightRounding = 0;
                                    if (is_object($val)) {
                                        $name = $val->name;
                                        $value = $val->value;
                                        $priority = $val->priority;
                                        $totalWeightRounding = $val->total_weight_rounding;
                                    }
                                    ?>
                                    <div class="form-group">
                                        <div class="form-field">
                                            <label for="shipping_name-<?= $i ?>"><?php _e('Name') ?></label>
                                            <input type="text" id="shipping_name-<?= $i ?>" name="shipping_name-<?= $i ?>" value="<?= stripslashes($name) ?>" />
                                        </div>
                                        <div class="form-field">
                                            <label for="shipping_priority-<?= $i ?>"><?php _e('Priority') ?></label>
                                            <input type="text" id="shipping_priority-<?= $i ?>" name="shipping_priority-<?= $i ?>" value="<?= stripslashes($priority) ?>"/>
                                        </div>
                                        <div class="form-field">
                                            <label for="shipping_total_weight_rounding-<?= $i ?>"><?php _e('Total Weight Rounding') ?></label>
                                            <select id="shipping_total_weight_rounding-<?= $i ?>" name="shipping_total_weight_rounding-<?= $i ?>">
                                                <option value="0" <?= $totalWeightRounding == 0 ? 'selected="selected"' : '' ?>><?php _e('None') ?></option>
                                                <option value="1" <?= $totalWeightRounding == 1 ? 'selected="selected"' : '' ?>><?php _e('Ceil') ?></option>
                                                <option value="-1" <?= $totalWeightRounding == -1 ? 'selected="selected"' : '' ?>><?php _e('Floor') ?></option>
                                            </select>
                                        </div>
                                        <div class="form-field">
                                            <label for="shipping_value-<?= $i ?>"><?php _e('Value') ?></label>
                                            <textarea id="shipping_value-<?= $i ?>" name="shipping_value-<?= $i ?>" rows="5" cols="50"><?= stripslashes($value) ?></textarea>
                                        </div>
                                    </div>
                                    <?
                                    $i++;
                                }
                            }
                            ?>
                            <div class="form-group">
                                <div class="form-field">
                                    <label for="shipping_name-<?= $i ?>"><?php _e('Name') ?></label>
                                    <input type="text" id="shipping_name-<?= $i ?>" name="shipping_name-<?= $i ?>" value="" />
                                </div>
                                <div class="form-field">
                                    <label for="shipping_priority-<?= $i ?>"><?php _e('Priority') ?></label>
                                    <input type="text" id="shipping_priority-<?= $i ?>" name="shipping_priority-<?= $i ?>" value=""/>
                                </div>
                                <div class="form-field">
                                    <label for="shipping_value-<?= $i ?>"><?php _e('Value') ?></label>
                                    <textarea name="shipping_value-<?= $i ?>" id="shipping_value-<?= $i ?>" rows="5" cols="50"></textarea>
                                </div>
                            </div>

                            <div class="submit"><input type="submit" name="shipping_byweight_submit" value="Update" /></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?
        $out = ob_get_contents();
        ob_end_clean();

        echo $out;
    }

    function handle_shipping() {
        do_action('warung_shipping_options');
    }

    function display_product_options() {
        do_action('warung_display_product_options');
    }

    function displayProductOptionsWithDiscount() {
        // Use nonce for verification
        global $post;


        // get prev meta
        $product = WarungProduct::getProductById($post->ID, false /* dont calculate discount */);

        //default values
        if (empty($product['stock'])) {
            $product['stock'] = '';
        }

        // product code
        // price
        // weight
        // option set
        ?>
        <input type="hidden" name="warung_noncename" id="warung_noncename"
               value="<?= wp_create_nonce(plugin_basename(__FILE__)) ?>" />
        <style type="text/css">
            .form-field label {font-weight: bold; display: block; padding: 5px 0pt 2px 2px;}
        </style>
        <div class="form-field">
            <label for="product_code"><?= __("Code") ?></label>
            <input type="text" name="product_code" value="<?= $product["code"] ?>"/>
            <p><?= __("Enter product code") ?></p>
        </div>
        <div class="form-field">
            <label for="product_price"><?= __("Price") ?></label>
            <input type="text" name="product_price" value="<?= $product["price"] ?>"/>
            <p><?= __("Enter product price") ?></p>
        </div>
        <div class="form-field ">
            <label for="product_weight"><?= __("Weight") ?></label>
            <input type="text" name="product_weight" value="<?= $product["weight"] ?>"/>
            <p><?= __("Enter the product weight") ?></p>
        </div>
        <div class="form-field ">
            <label for="product_stock"><?= __("Product Stock") ?></label>
            <input type="text" name="product_stock" value="<?= $product["stock"] ?>"/>
            <p><?= __("Enter the product stock or leave blank if stock is unlimited.") ?></p>
        </div>
        <div class="form-field ">
            <label for="product_show_stock"><?= __("Show Available Product Stock?") ?></label>
            <input type="checkbox" name="product_show_stock" value="show_stock" <?= !empty($product["show_stock"]) ? 'checked="checked"' : '' ?>/>
            <p><?= __("Whether to show product stock number or not") ?></p>
        </div>
        <?
        // get from option
        $opt = new WarungOptions();
        $options = $opt->getOptions();
        
        $prod_options = $options;
        $prod_options = $prod_options["prod_options"];
        // get from product custom field

        if (is_array($prod_options) && !empty($prod_options)) {
            ?>
            <div class="form-field ">
                <label for="product_options"><?= __("Option Set") ?></label>
                <select name="product_options">
                    <option value="-- none --">-- none --</option><?
            foreach ($prod_options as $key => $value) {
                if ($product["option_name"] == $value->name) {
                    ?><option value="<?= $value->name ?>" selected="selected"><?= $value->name ?></option><?
                } else {
                    ?><option value="<?= $value->name ?>"><?= $value->name ?></option><?
                }
            }
            ?></select>
                <p><?= __("Choose option set") ?></p>
            </div><?
        }
        ?>
        <h4><?= __("Discount") ?></h4>
        <div class="form-field ">
            <label for="product_price_discount"><?= __("Discounted Price") ?></label>
            <input type="text" name="product_price_discount" value="<?= isset($product["price_discount"]) ? $product["price_discount"] : ''; ?>"/>
            <p><?= __("Enter discounted price if any. Example 10000") ?></p>
        </div>

        <div class="form-field ">
            <label for="product_weight_discount"><?= __("Discounted Weight") ?></label>
            <input type="text" name="product_weight_discount" value="<?= isset($product["weight_discount"]) ? $product["weight_discount"] : ''; ?>"/>
            <p><?= __("Enter discounted weight if any. Example: 1") ?></p>
        </div>
        
        <?
        $shippingServices = GenericShippingService::getAllServices();
        $defaultService = GenericShippingService::getDefaultService();
        $defaultServiceId = "";
        if ($defaultService) {
            $defaultServiceId = $defaultService->id;
        }
        ?>
        
        <div class="form-field ">
            <label for="product_shipping"><?= __("Product Shipping") ?></label>
            <select name="product_shipping">
                <option value="<?=$defaultServiceId?>">Default</option><?
        foreach ($shippingServices as $shipping) {
            if ($product["shipping"] == $shipping->id) {
                ?><option value="<?= $shipping->id ?>" selected="selected"><?= $shipping->name . ($shipping->id == $defaultServiceId?" (Default)":"")?></option><?
            } else {
                ?><option value="<?= $shipping->id ?>"><?= $shipping->name . ($shipping->id == $defaultServiceId?" (Default)":"")?></option><?
            }
        }
        ?></select>
            <p><?= __("Choose shipping service") ?></p>
        </div>
        
        <?
    }

    function save_product_details($post_id) {
        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times

        if (!wp_verify_nonce($_POST['warung_noncename'], plugin_basename(__FILE__))) {
            return $post_id;
        }

        // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
        // to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return $post_id;


        // Check permissions
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        } else {
            if (!current_user_can('edit_post', $post_id))
                return $post_id;
        }

        // OK, we're authenticated: we need to find and save the data
        // get product title

        $prod_code = $_POST['product_code'];
        $prod_name = get_post($post_id)->post_title;
        $prod_price = $_POST['product_price'];
        $prod_weight = $_POST['product_weight'];
        $prod_options = $_POST['product_options'];
        $prod_weight_discount = $_POST['product_weight_discount'];
        $prod_stock = $_POST['product_stock'];
        $prod_show_stock = $_POST['product_show_stock'];
        $prod_price_discount = $_POST['product_price_discount'];
        $prod_shipping = $_POST['product_shipping'];

        if (!empty($prod_code) && !empty($prod_name)) {
            update_post_meta($post_id, '_warung_product_code', $prod_code);
            if (empty($prod_price)) {
                $prod_price = 0;
            }
            if (empty($prod_weight)) {
                $prod_weight = 1;
            }
            update_post_meta($post_id, '_warung_product_price', $prod_price);
            update_post_meta($post_id, '_warung_product_weight', $prod_weight);
            update_post_meta($post_id, '_warung_product_weight_discount', $prod_weight_discount);
            if ($prod_options != '-- none --') {
                update_post_meta($post_id, '_warung_product_options', $prod_options);
            } else {
                delete_post_meta($post_id, '_warung_product_options');
            }
            update_post_meta($post_id, '_warung_product_stock', $prod_stock);
            update_post_meta($post_id, '_warung_product_show_stock', $prod_show_stock);
            update_post_meta($post_id, '_warung_product_price_discount', $prod_price_discount);
            
            if (!empty($prod_shipping)) {
                update_post_meta($post_id, '_warung_product_shipping', $prod_shipping);
            }
        }
    }

}

/**
 * Description of WarungAdminOrder
 *
 * @author hendra
 */
class WarungAdminOrder {
    function handle_orders() {
        $orderService = new OrderService();

        //check_admin_referer('warung-nonce')
        if ( !empty($_REQUEST['wrg_order_status_submit'])
                && !empty($_REQUEST['wrg_order_status_id'])
                && !empty($_REQUEST['wrg_order_status_status'])) {
            // update status
            $orderService->updateStatus($_REQUEST['wrg_order_status_id'], $_REQUEST['wrg_order_status_status']);
        }

        // current url
        $pageURL = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];

        // nav
        $page = 1;
        if (isset($_REQUEST['wrg_order_page'])) {
            $page = $_REQUEST['wrg_order_page'];
        }

        // order by
        $orderBy = 'id asc';
        if (isset($_REQUEST['wrg_order_sortby'])) {
            $orderBy = $_REQUEST['wrg_order_sortby'];
        }

        // status order
        $orderStatusClass = "asc";
        $orderByStatusURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"status asc"));
        if ($orderBy == 'status desc') {
            $orderStatusClass = "asc";
            $orderByStatusURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"status asc"));
        } else {
            $orderStatusClass = "desc";
            $orderByStatusURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"status desc"));
        }

        // id order
        $orderIdClass = "asc";
        $orderByIdURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"id asc"));
        if ($orderBy == 'id desc') {
            $orderIdClass = "asc";
            $orderByIdURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"id asc"));
        } else {
            $orderIdClass = "desc";
            $orderByIdURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"id desc"));
        }

        // date order
        $orderDateClass = "asc";
        $orderByDateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtcreated asc"));
        if ($orderBy == 'dtcreated desc') {
            $orderDateClass = "asc";
            $orderByDateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtcreated asc"));
        } else {
            $orderDateClass = "desc";
            $orderByDateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtcreated desc"));
        }

        // lastupdate order
        $orderLastUpdateClass = "asc";
        $orderByLastUpdateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtlastupdated asc"));
        if ($orderBy == 'dtlastupdated desc') {
            $orderLastUpdateClass = "asc";
            $orderByLastUpdateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtlastupdated asc"));
        } else {
            $orderLastUpdateClass = "desc";
            $orderByLastUpdateURL = WarungURLUtil::updateParams($pageURL, array("wrg_order_sortby"=>"dtlastupdated desc"));
        }

        // get all order
        $orders = $orderService->getAllOrders(5, $page, $orderBy);
        $orderData = array();
        if (isset($orders['data'])) {
            $orderData = $orders['data'];
        }
        $orderStatuses = $orderService->getAllStatus();

        // page nav
        $pageNav = new PageNav('wrg_order_page', $orders);

        $chartURL = WarungChart::getOrderChartURL(400,150);
        ?>
<div class="wrap">
    <h2>Order</h2>
    
    <div class="wschart">
        <img alt="order chart" src="<?=$chartURL?>"/>
    </div>
    <div class="tablenav"><?=$pageNav->show(' ', '«', '»')?></div>
    <div class="clear"></div>
    <table class="wp-list-table widefat">
        <thead>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable <?=$orderIdClass?>" style=""><a href="<?=$orderByIdURL?>"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable <?=$orderDateClass?>" style=""><a href="<?=$orderByIdURL?>"><span>Order Date</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable <?=$orderStatusClass?>" style=""><a href="<?=$orderByStatusURL?>"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="updatedate" class="manage-column column-date sortable <?=$orderLastUpdateClass?>" style=""><a href="<?=$orderByLastUpdateURL?>"><span>Last Update</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</thead>

	<tfoot>
            <tr>
                <th scope="col" id="cb" class="manage-column column-cb check-column" style=""><input type="checkbox"></th>
                <th scope="col" id="id" class="manage-column num sortable <?=$orderIdClass?>" style=""><a href="<?=$orderByIdURL?>"><span>ID</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="date" class="manage-column column-date sortable <?=$orderDateClass?>" style=""><a href="<?=$orderByIdURL?>"><span>Order Date</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="buyer" class="manage-column column-author sortable desc" style=""><a href="http://localhost/%7Ehendra/wp/wp-admin/edit.php?orderby=author&amp;order=asc"><span>Buyer</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="items" class="manage-column column-categories" style="">Items</th>
                <th scope="col" id="shipping" class="manage-column column-tags" style="">Shipping</th>
                <th scope="col" id="status" class="manage-column column-comments num sortable <?=$orderStatusClass?>" style=""><a href="<?=$orderByStatusURL?>"><span>Status</span><span class="sorting-indicator"></span></a></th>
                <th scope="col" id="updatedate" class="manage-column column-date sortable <?=$orderLastUpdateClass?>" style=""><a href="<?=$orderByLastUpdateURL?>"><span>Last Update</span><span class="sorting-indicator"></span></a></th>
            </tr>
	</tfoot>

	<tbody id="the-list">
<?
        if (sizeof($orderData) > 0) {

        foreach($orderData as $order) {
?>
        <tr id="order-<?=$order->id?>" class="alternate author-self status-publish format-default iedit" valign="top">
            <th scope="row" class="check-column"><input name="post[]" value="1" type="checkbox"></th>
            <td class=""><strong><a class="" href="http://localhost/%7Ehendra/wp/wp-admin/post.php?post=1&amp;action=edit" title="Edit “Hello world!”"><?=$order->id?></a></strong></td>
            <td class="date column-date"><abbr title="<?=$order->dtcreated?>"><?=$order->dtcreated?></abbr></td>
            <td class="author column-author"><a href="edit.php?post_type=post&amp;author=1"><?=$order->getBuyerName()?></a></td>
            <td class="categories column-categories"><a href="edit.php?post_type=post&amp;category_name=uncategorized"><?=str_replace(",","<br/>",$order->getItemsSummary())?></a></td>
            <td class="tags column-tags"><?=$order->getShippingAddress()?></td>
            <td class="comments column-comments">
                <form id="wrg_order_status_form_<?=$order->id?>" name="wrg_order_status_form_<?=$order->id?>" method="POST">
                    <?wp_nonce_field('wrg_order_status_nonce')?>
                    <input type="hidden" name="wrg_order_status_id" value="<?=$order->id?>"/>
                    <?=WarungUtils::htmlSelect("wrg_order_status_status_".$order->id, "wrg_order_status_status", $orderStatuses, $order->status)?>
                    <input type="submit"value="Update" name="wrg_order_status_submit"/>
                </form>
            </td>
            <td class="date column-date"><abbr title="<?=$order->dtlastupdated?>"><?=$order->dtlastupdated?></abbr></td>
        </tr>
<?
        }

        } else {
            ?>
        <tr id="order-<?=$order->id?>" class="alternate author-self status-publish format-default iedit" valign="top">
            <td colspan="8" style="text-align: center">Empty Order</td>
        </tr>
            <?
        }
?>
        </tbody>
    </table>
    <div class="tablenav"><?=$pageNav->show(' ', '«', '»')?></div>
</div>
<?
    }
}
?>
