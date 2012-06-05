<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function wrg_do_views($content) {
    global $post;

    $wo = new WarungOptions();

    $co_page = $wo->getCheckoutPageId();
    $shipping_sim_page = $wo->getShippingSimPageId();

    if ($post->ID == $co_page) {
        $content = wrg_checkoutPage();
    } else if ($post->ID == $shipping_sim_page) {
        $content .= wrg_shippingSimPage();
    } else {
        $content .= wrg_addToCartPage();
    }

    return $content;
}

function wrg_checkoutPage() {
    
}

function wrg_shippingSimPage() {
    
}

function wrg_addToCartPage() {
    global $post;

    ob_start();

    // check is this post contains product informations

    $product = WarungProduct::getProductById($post->ID);
    if (!empty($product) && !is_search()) {
        if (isset($product["option_text"])) {
            echo stripslashes($product["option_text"]);
        }

        $disc_price = null;
        if (isset($product ['price_discount'])) {
            $disc_price = $product['price_discount'];
        }
        ?>
        <div id="wCart_add_2_cart">
            <form method="POST">
                <input type="hidden" name="product_id" value="<?= $product["id"] ?>">
                <?
                if (!empty($product["option_value"])) {
                    ?>
                    <h3><?= _e('Pilih Produk') ?></h3>
                    <div class="wcart_product_opt">
                        <?
                        $isRadioOption = true;
                        if ($isRadioOption) {

                            $hasDefault = false;
                            foreach ($product["option_value"] as $po) {
                                if (isset($po->default)) {
                                    $hasDefault = true;
                                }
                            }

                            foreach ($product["option_value"] as $po) {
                                $checked = "";

                                // set default to first entry if no default given
                                if (!$hasDefault && empty($checked)) {
                                    $checked = "checked=checked";
                                }

                                if (isset($po->default)) {
                                    $checked = "checked=checked";
                                }
                                ?>
                                <input type="radio" name="product_option" id="a2c-r-<?= $po->id ?>" value="<?= $po->id ?>" <?= $checked ?>/>
                                <label for="a2c-r-<?= $po->id ?>">
                                    <?= $po->name . '<span>' . WarungUtils::formatCurrency($po->price) . '</span>' ?>
                                </label><br/><?
                }
            } else {
                                ?>

                            <select name="product_option" class="wcart_price" size="<?= max(1, sizeof($product["option_value"]) / 3) ?>">
                                <?
                                foreach ($product["option_value"] as $po) {
                                    $selected = "";
                                    if (isset($po->default)) {
                                        $selected = 'selected="selected"';
                                    }
                                    ?>
                                    <option value="<?= $po->id ?>" <?= $selected ?>><?= $po->name . '<span>' . WarungUtils::formatCurrency($po->price) . '</span>' ?></option>
                                    <?
                                }
                                ?>
                            </select>
                            <?
                        }
                        ?></div><?
        } else {
            if (isset($disc_price) && !empty($disc_price)) {
                            ?>
                        <span><s><?= WarungUtils::formatCurrency($disc_price) ?></s></span>
                        <span><?= WarungUtils::formatCurrency($product["price"]) ?></span>
                        <?
                    } else {
                        ?>
                        <span class="ws_price"><?= WarungUtils::formatCurrency($product["price"]) ?></span>
                        <?
                    }
                }
                $wo = new WarungOptions();
                $options = $wo->getOptions();
                ?>
                <input type="submit" name="wcart_ordernow" value="<?= $options["add_to_cart"] ?>"/>
            </form>
        </div>

        <?
    }



    $ret = ob_get_contents();

    ob_clean();

    return $ret;
}
?>
