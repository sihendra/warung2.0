<?php
/**
 * Description of WarungCartWidget
 *
 * @author hendra
 */
class WarungCartWidget extends WP_Widget {


    function __construct() {
        $widget_ops = array('classname' => 'wcart_widget', 'description' => 'Warung Cart Shopping Cart');
        parent::__construct(false, $name = 'Warung Cart', $widget_ops);
    }

    public function widget($args, $instance) {

        $wo = new WarungOptions();

        $cartImage = WARUNG_ROOT_URL . "images/cart.png";
        $co_page = $wo->getCheckoutURL();
        $clear_page = add_query_arg(array("wrg_action" => "clearCart"),get_option("home"));

        // get cart info
        $user = new WarungUser2();
        $cart = new WarungCart2();
        $kasir = new WarungKasir2();
        
        $cartSummary = $kasir->getSummary($cart->getItems(), $user->getUserName(), $user->getShippingDestination);
        
        extract($args);
        // e.get cart info
        
        $title = apply_filters('widget_title', $instance['title']);
        ?>
        <?php echo $before_widget; ?>
        <?php if ($title)
            echo $before_title . '<a href="' . $co_page . '"><img src="' . $cartImage . '" alt="shopping cart"/> Keranjang Belanja</a>' . $after_title;
        ?>

        <? if (isset($cartSummary["totalItems"]) && $cartSummary["totalItems"]>0) : ?>
            <div><a href="<?= $co_page ?>">Ada <?= $cartSummary["totalItems"] ?> Item (<?= WarungUtils::formatCurrency($cartSummary["totalPrice.grand"]) ?>)</a></div>
            <div class="wcart_widget_nav"><a href="<?= $clear_page ?>">Batal</a></div>
        <? else: ?>
            <div>0 Item, <a href="<?php echo get_option("home")?>" style="text-decoration: none;">ayo dipilih!</a></div>
        <? endif; ?>
        <?php echo $after_widget; ?>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);

        return $instance;
    }

    public function form($instance) {
        $instance = wp_parse_args((array) $instance, array('title' => 'Shopping Cart'));
        $title = strip_tags($instance['title']);
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
        <?php
    }

}
?>
