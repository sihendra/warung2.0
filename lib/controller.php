<?php

// controller
function wrg_do_action() {
    // required by cart
    session_start();

    $a = $_REQUEST['wrg_action'];
    if ($a == 'updateCart' && wp_verify_nonce($_REQUEST['warung_detailed_cart_nonce'], 'warung_detailed_cart')) {
        // UPDATE CART
        $cart = new WarungCart2();
        foreach ($_REQUEST as $key => $val) {
            if (strpos($key, 'qty_') !== false) {
                //echo $key.'->'.$val;
                $tok = explode('_', $key);
                if (count($tok) == 2) {
                    $cart->updateQuantity($tok[1], $val);
                }
            }
        }

        add_filter('the_content', 'wrg_showCheckoutPage');
    } else if ('clearCart' == $a) {
        // CLEAR CART
        $cart = new WarungCart2();
        $cart->emptyCart();
    } else if ($a == 'removeCartItem' && isset($_REQUEST['ci'])) {
        // REMOVE CART ITEM
        $cart = new WarungCart2();
        $cart->updateQuantity($_REQUEST['ci'], 0);
    } else if (isset($_POST['add_to_cart'])) {
        // ADD 2 CART
        $added_product = WarungProduct::getProductById($_POST['product_id']);
        $item = WarungUtils::formatToKeranjangItem($added_product, $_POST["product_option"]);

        $cart = new WarungCart2();
        $cart->addItem($item, 1);
    } else if (isset($_POST['wcart_ordernow'])) {
        
        // ORDER NOW
        $added_product = WarungProduct::getProductById($_POST['product_id']);
        
        $item = WarungUtils::formatToKeranjangItem($added_product, $_POST["product_option"]);

        $cart = new WarungCart2();
        $cart->addItem($item, 1);

        // redirect to checkoutpage
        $opt = new WarungOptions();
        header("Location: " . $opt->getCheckoutURL());
        exit;
    } else if ($a == 'confirm' && wp_verify_nonce($_REQUEST['warung_shipping_form_nonce'], 'warung_shipping_form')) {
        // save user info 
        $kasir = new WarungKasir2();
        extract($_REQUEST);

        if (!empty($sname)) {
            $userInfo = (object)array(
                'name'=>$sname, 
                'email'=>$semail, 
                'phone'=>$sphone, 
                'address'=>$saddress, 
                'city'=>$scity, 
                'additionalInfo'=>$sadditional_info,
                'postalCode'=>$spostalcode,
                'paymentMethod'=>$spaymentmethod);
            $kasir->saveUserInfo($userInfo);
        }
        
        add_filter('the_content',  "wrg_showOrderConfirmation");
        return;
    } else if($a=='pay') {
        add_filter('the_content', 'wrg_showOrderComplete');
        return;
    }
    
    // default filter
    add_filter('the_content', 'wrg_showDefault');
    
        
}


// VIEW functions

function wrg_showShippingForm($showUpdateForm = true) {
    ob_start();

    // get option
    $warungOpt = new WarungOptions();

    // get kasir
    $kasir = new WarungKasir2();

    // get user info
    $userInfo = $kasir->getSavedUserInfo();

    // get city
    $cities = GenericShippingService::getAllDestinations();
    $city=null;
    if (isset($userInfo->city)) {
        $city = $userInfo->city;
    }
    ?>
    <div class="wcart_shipping_container">
        <div><a name="w_shipping"/><h2>Informasi Pengiriman</h2></div>
        <div id="wCart_shipping_form">
            <? if ($showUpdateForm) : ?>
                <form method="POST" name="wCart_shipping_form" id="wCart_shipping_form2" action="<?= add_query_arg('wrg_action', 'confirm') ?>">
                    <? wp_nonce_field('warung_shipping_form', 'warung_shipping_form_nonce'); ?>
                <? endif; ?>
                <div class="wCart_form_row">
                    <label for="semail">Email *</label>
                    <? if ($showUpdateForm) : ?>
                        <input type="text" name="semail" value="<?= $userInfo->email ?>" maxlength="60"/>
                    <? else: ?>
                        <span><?= $userInfo->email ?></span>
                    <? endif; ?>
                </div>

                <div class="wCart_form_row">
                    <label for="sphone">HP (handphone) *</label>
                    <? if ($showUpdateForm) : ?>
                        <input type="text" name="sphone" value="<?= $userInfo->phone ?>" maxlength="31"/>
                    <? else: ?>
                        <span><?= $userInfo->phone ?></span>
                    <? endif; ?>
                </div>
                <div class="wCart_form_row">
                    <label for="sname">Nama Penerima *</label>
                    <? if ($showUpdateForm) : ?>
                        <input type="text" name="sname" value="<?= $userInfo->name ?>" maxlength="60"/>
                    <? else: ?>
                        <span><?= $userInfo->name ?></span>
                    <? endif; ?>
                </div>
                <div class="wCart_form_row">
                    <label for="saddress">Alamat *</label>
                    <? if ($showUpdateForm) : ?>
                        <textarea name="saddress" ><?= $userInfo->address ?></textarea>
                    <? else: ?>
                        <span><?= $userInfo->address ?></span>
                    <? endif; ?>
                </div>
                <div class="wCart_form_row">
                    <label for="scity">Kota/Kec *</label>
                    <? if ($showUpdateForm) : ?>
                        <?= WarungUtils::htmlSelect('scity', 'scity', $cities, $city,'','-- Pilih Kota --') ?>
                    <? else: ?>
                        <span><?= $userInfo->city ?></span>
                    <? endif; ?>
                </div>
                <div class="wCart_form_row">
                    <label for="spostalcode">Kode Pos *</label>
                    <? if ($showUpdateForm) : ?>
                        <input type="text" name="spostalcode" value="<?= $userInfo->postalCode ?>" maxlength="60"/>
                         <a href="http://kodepos.posindonesia.co.id/" target="_blank">&nbsp;kode pos</a>
                    <? else: ?>
                        <span><?= $userInfo->postalCode ?></span>
                    <? endif; ?>
                </div>
                <div class="wCart_form_row">
                    <label for="spaymentmethod">Pembayaran *</label>
                    <? if ($showUpdateForm) : ?>
                        <?= WarungUtils::htmlSelect('spaymentmethod', 'spaymentmethod', array(""=>"-- pilih metode pembayaran --","bca"=>"Transfer ke BCA","mandiri"=>"Transfer ke Mandiri"), $userInfo->paymentMethod) ?>
                    <? else: ?>
                        <span><?= $userInfo->paymentMethod ?></span>
                    <? endif; ?>
                </div>
                <div class="wCart_form_row">
                    <label for="sadditional_info">Info Tambahan</label>
                    <? if ($showUpdateForm) : ?>
                        <textarea name="sadditional_info"><?= $userInfo->additionalInfo ?></textarea>
                    <? else: ?>
                        <span><?= $userInfo->additionalInfo ?></span>
                    <? endif; ?>
                </div>
                <input type="hidden" name="scountry" value="<?= $userInfo->country ?> *"/>
                
                <? if ($showUpdateForm) : ?>

                    <div class="wCart_form_row">
                        <input type="hidden" name="step" value="2"/>
                        <input type="submit" name="scheckout" class="submit" value="Lanjut"/>
                    </div>

                </form>
            <? endif; ?>
        </div>
    </div>
    <?
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showDetailedCart($showUpdateForm = true) {
    ob_start();

    // show cart
    $homePageURL = get_option("home");
    $cart = new WarungCart2();
    $kasir = new WarungKasir2();
    $userInfo = $kasir->getSavedUserInfo();

    // count cart summary
    $user = null;
    $destination = null;
    if (isset($userInfo->name)) {
        $user = $userInfo->name;
    }
    if (isset($userInfo->city)) {
        $destination = $userInfo->city;
    }

    $cartEntry = $cart->getItems();
    $cartSum = $kasir->getSummary($cartEntry, $userInfo, $destination);
    
    ?>
    <div class="wcart_detailed_cart_container">
        <?
        if (!empty($cartEntry)) {

            $clearPage = add_query_arg('wrg_action', 'clearCart');
            $actionURL = add_query_arg('wrg_action', 'updateCart');
            ?>
            <div><a name="w_cart"/><h2><? _e('Keranjang Belanja') ?></h2></div>
            <div id="wcart-detailed-div">
                <?
                if ($showUpdateForm) {
                    ?>
                    <form method="POST" action="<?= $actionURL ?>">
                        <? wp_nonce_field('warung_detailed_cart', 'warung_detailed_cart_nonce'); ?>
                        <?
                    }
                    ?>
                    <table id="wcart-detailed">
                        <tr><th><? _e('Item') ?></th><th><? _e('Berat') ?></th><th><? _e('Harga') ?></th><th><? _e('Jumlah') ?></th><th><? _e('Total') ?></th><th>-</th></tr>
                        <?
                        foreach ($cartEntry as $i) {
                            //name|price[|type]
                            $removePage = add_query_arg(array("wrg_action" => "removeCartItem", "ci" => $i->cartId));
                            $productInfo = $i->attachment["product"];
                            $productURL = get_permalink($i->productId);
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <div id="wcart_item_thumbnail"><a href="<?= $productURL ?>"><img src="<?= $productInfo["thumbnail"] ?>" alt="<?= $i->name ?>"/></a></div>
                                        <div id="wcart_pinfo"><?= $i->name ?></div>
                                    </div>
                                </td>
                                <td><?= WarungUtils::formatWeight($i->weight) ?></td>
                                <td><?= WarungUtils::formatCurrency($i->price) ?></td>
                                <td><? if ($showUpdateForm) : ?>
                                        <input type="text" name="qty_<?= $i->cartId ?>" value="<?= $i->quantity ?>" size="1" maxlength="5"/>
                                        <?
                                    else:
                                        echo $i->quantity;
                                    endif;
                                    ?>
                                </td>
                                <td><?= WarungUtils::formatCurrency($i->price * $i->quantity) ?> </td>
                                <? if ($showUpdateForm) : ?>
                                    <td><a class="wcart_remove_item" href="<?= $removePage ?>"><div><span>(X)</span></div></a></td>
                                <? endif; ?>
                            </tr>

                            <?
                        }

                        if ($showUpdateForm) :
                            ?>
                            <tr><td colspan="3" class="wcart-td-footer">&nbsp</td><td class="wcart-td-footer"><input type="submit" name="wc_update" value="Update" title="Klik tombol ini jika ingin mengupdate jumlah barang"/></td><td class="wcart-td-footer">&nbsp;</td></tr>
                        <? endif; ?>
                        <tr><td colspan="4" class="wcart-td-footer">Total Sebelum Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["totalPrice"]) ?></span></td></tr>
        <?php if (isset($cartSum["shipping"])) : ?>
                            <tr>
                                <td colspan="4" class="wcart-td-footer">Ongkos Kirim (<?= WarungUtils::formatWeight($cartSum["totalWeight"]) ?>)<?php echo !empty($cartSum["shipping.name"]) ? " - ".$cartSum["shipping.name"]: ''; ?></td>
                                <td class="wcart-td-footer"><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["shipping"]) ?></span></td></tr>
                            <?php if(!empty($cartSum["discount"])):?>
                            <tr>
                                <td colspan="4" class="wcart-td-footer">Diskon</td>
                                <td class="wcart-td-footer"><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["discount"]) ?></span></td>
                            </tr>
                            <?php endif; ?>
                            <tr><td colspan="4" class="wcart-td-footer">Total Setelah Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["totalPrice.grand"]) ?></span></td></tr>
                            <?php if(isset($cartSum["shipping.error"])):foreach($cartSum["shipping.error"] as $err){?>
                            <tr>
                                <td colspan="4" class="wcart-td-footer">Catatan</td>
                                <td class="wcart-td-footer"><span class="wcart_total"><?= $err ?></span></td>
                            </tr>
                            <?php } endif; ?>
                    <? endif; ?>
                    </table>
        <? if ($showUpdateForm) : ?>
                        <div id="wcart_detailed_nav">
                            <a href="<?= $homePageURL ?>" class="wcart_button_url">Kembali Berbelanja</a> atau isi form di bawah ini jika ingin lanjut ke pemesanan.
                        </div>

                    </form>
            <? endif; ?>
            </div>
            <?
        } else {
            ?>
            <p id="status"><?php _e('Keranjang belanja kosong') ?><a href="<?= $homePageURL ?>" class="wcart_button_url"> <?php _e('Lihat Produk') ?></a></p><?php
    }
    ?>
    </div>
    <?
    $ret = ob_get_contents();
    ob_end_clean();


    return $ret;
}

function wrg_showCheckoutPage($content) {
    $updatable = true;
    
    // show cart entry
    $ret = wrg_showDetailedCart($updatable);

    // add form if cart is not empty
    $cart = new WarungCart2();
    if (!$cart->isEmpty()) {
        $ret .= wrg_showShippingForm($updatable);
    }

    return $ret;
}

function wrg_doProduct($content) {

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
    

    return $content.$ret;
}

function wrg_showDefault($content) {
    global $post;
    
    $opt = new WarungOptions();
    $co_page= $opt->getCheckoutPageId();
    $admin_page = $opt->getAdminPageId();
    $shipping_page = $opt->getShippingSimPageId();

    if ($post->ID == $co_page) {
        return wrg_showCheckoutPage($content);
    } else if ($post->ID == $admin_page) {
        return wrg_showAdminPage($content);
    } else if ($post->ID == $shipping_page) {
        return wrg_showShippingSimPage($content);
    } else {
        return wrg_doProduct($content);
    }    
}

function wrg_showShippingSimPage($content) {
    ob_start();
    
    $cities = GenericShippingService::getAllDestinations();
    $simURL = add_query_arg("wrg_action","shipping_sim");
    
    // param
    $weight = $_REQUEST["weight"];
    $destination = $_REQUEST['city'];
    $origin = 'jakarta';
    
    if (empty($weight)) {
        $weight = 1;
    }
    
    $res = array();
    if (isset($_REQUEST["shipping_sim"])) {
        
        $services = GenericShippingService::getAllServices();
        foreach($services as $service) {
            $s = new GenericShippingService($service->id, $origin);
            $price = $s->getPrice($destination, $weight);
            
            if ($price > 0) {
                $res[$s->getServiceName()] = $price;
            }
        }
    }
    ?>
    <form action="<?=$simURL?>" method="POST">
        <?= WarungUtils::htmlSelect('city', 'city', $cities, $destination, '', '-- Pilih Kota --') ?>
        <input type="text" name="weight" value="<?=$weight?>"> Kg
        <input type="submit" value="Cek Ongkir" name="shipping_sim">
    </form>
    <?php if (!empty($res)): asort($res)?>
        <div class="alert">
        <?php foreach($res as $name=>$price) :?>
            <li><?= WarungUtils::formatCurrency($price). " (".$name.")"?> </li>
        <?php endforeach;?>
        </div>
    <?php endif; ?>
    <?
    
    $ret .= $content . ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showOrderConfirmation() {
    ob_start();

    $ret = "";
    
    // get edit url
    $wo = new WarungOptions();
    $editURL = $wo->getCheckoutURL();

    $cart = new WarungCart2();
    
    if ($cart->isEmpty()) {
        // empty cart hider form subscription
        echo wrg_showDetailedCart(false);
    } else {
    
    $ret = "Jika data sudah benar, klik tombol 'Pesan' di bawah, atau jika masih ada yang salah klik tombol 'Edit' untuk membenarkan";

    echo wrg_showDetailedCart(false);
    // show edit url
    ?>
    <p><a class="wcart_button_url" href="<?= $editURL . "#w_cart" ?>">Edit</a></p>
    <?
    echo wrg_showShippingForm(false);
    // show edit url
    ?>
    <p><a class="wcart_button_url" href="<?= $editURL . "#w_shipping" ?>">Edit</a></p>
    <div style="padding: 10px;">
        <form method="POST" id="wCart_confirmation" action="<?= add_query_arg("wrg_action","pay") ?>">
            <input type="submit" name="send_order" value="Pesan"/>
        </form>
    </div>
    <?
    }
    
    $ret .= ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showOrderComplete() {
    ob_start();

    // ------ LOGIC -------------
    // process flag
    $orderOk = FALSE;

    $cart = new WarungCart2();
    $kasir = new WarungKasir2();

    if ($cart->isEmpty()) {
        // empty cart hider form subscription
        echo wrg_showDetailedCart(false);
    } else {
        
    
    $userInfo = $kasir->getSavedUserInfo();
    $cartSum = $kasir->getSummary($cart->getItems(), $userInfo, $userInfo->city);

    // save order
    $order = (object)array();
    $order->items = $cart->getItems();
    $order->totalPrice = $cartSum["totalPrice"];
    $order->shippingInfo = $userInfo;
    $order->buyerInfo = $userInfo;
    $order->shippingWeight = $cartSum["totalWeight"];
    $order->shippingPrice = $cartSum["shipping"];
    $order->statusId = OrderService::$STATUS_NEW;
    $order->shippingServices = $cartSum["shipping.services"];
    $order->paymentMethod = $userInfo->paymentMethod;

    $orderService = new OrderService();
    $order_id = $orderService->putOrder($order);

    if ($order_id !== FALSE) {
        $orderOK = TRUE;
    }

    // send email to admin & customer
    $admin_email = get_option("admin_email");
    $email_pemesan = $userInfo->email;
    $subject = "[Warungsprei.com] Pemesanan #" . $order_id;
    $message = WarungUtils::generateTemplate(
            wrg_getOrderMessageTemplate(), 
            array(
                "user.name" =>$userInfo->name,
                "cart.entries" => wrg_showDetailedCart(false),
                "user.email" =>$userInfo->email,
                "user.phone" =>$userInfo->phone,
                "user.address" =>$userInfo->address,
                "user.city" =>$userInfo->city,
                "user.additionalInfo" =>$userInfo->additionalInfo,
                "user.postalCode" =>$userInfo->postalCode,
                "user.paymentMethod" =>$userInfo->paymentMethod,
                "totalPrice.grand" =>WarungUtils::formatCurrency($cartSum["totalPrice.grand"]),
                ));

    $headers = "Content-type: text/html;\r\n";
    $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";

    // send to pemesan bcc admin
    $headers .= "Bcc: " . $admin_email . "\r\n";
    mail($email_pemesan, $subject, $message, $headers);


    // ------ e.LOGIC -----------
    
    $cartItems = $cart->getItems();

    if (empty($cartItems)) {
        ob_end_clean();
        return __('Keranjang belanja kosong');
    }
    
    
    $home_url = get_option('home');

    if ($orderOK) : ?>
    
    <div class="wcart_info">
        <p>Informasi pemesanan juga sudah kami kirim ke <b>'<?= $email_pemesan ?>'.</b> Mohon periksa juga folder <b>'Junk'</b> jika tidak ada di inbox.</p>
    </div>
    <?php echo $message; ?>
    <div><br/><a href="<?= $home_url ?>" class="wcart_button_url">Kembali berbelanja &gt;&gt;</a></div>
    <?
    
        // empty cart
        $cart->emptyCart();

    else:
        echo '<p>' . __('Maaf kami blm dapat memproses pesanan anda silahkan coba beberapa saat lagi') . '</p>';
    endif;
    
    }
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_getOrderMessageTemplate() {
    ob_start();
    ?>
    <div>
        <p>%user.name%, kami sudah menerima pesanan anda. Untuk pembayaran silahkan transfer ke salah satu nomor rekening berikut sebesar <b>%totalPrice.grand%</b>:
        <ul>
            <li>BCA: 5800106950 a.n. Hendra Setiawan</li>
            <li>Mandiri: 1270005578586 a.n. Hendra Setiawan</li>
        </ul>
        <br/>
        Setelah pembayaran dilakukan harap lakukan konfirmasi pembayaran agar pesanan dapat segera kami proses.
        Konfirmasi dapat dilakukan dengan cara me-reply email pemesanan ini atau menghubungi kami di:
        <ul>
            <li>HP: 08889693342, 081808815326 </li>
            <li>Email: info@warungsprei.com</li>
            <li>YM: reni_susanto, warungsprei_hendra</li>
        </ul>
        <br/>
        <br/>
        Terima Kasih,<br/>
        Warungsprei.com<br/>
        -----------------------------------
        <br/>
        %cart.entries%
        <br/>
        <br/>
        <!--shipping info-->
        <div><h2>Informasi Pengiriman</h2></div>
        <table>
            <tr><td>Email</td><td>&nbsp;:&nbsp;</td><td>%user.email%</td></tr>
            <tr><td>Telepon</td><td>&nbsp;:&nbsp;</td><td>%user.phone%</td></tr>
            <tr><td>Nama Penerima</td><td>&nbsp;:&nbsp;</td><td>%user.name%</td></tr>
            <tr><td>Alamat</td><td>&nbsp;:&nbsp;</td><td>%user.address%</td></tr>
            <tr><td>Kota</td><td>&nbsp;:&nbsp;</td><td>%user.city%</td></tr>
            <tr><td>Kode Pos</td><td>&nbsp;:&nbsp;</td><td>%user.postalCode%</td></tr>
            <tr><td>Pembayaran</td><td>&nbsp;:&nbsp;</td><td>%user.paymentMethod%</td></tr>
            <tr><td>Info Tambahan</td><td>&nbsp;:&nbsp;</td><td>%user.additionalInfo%</td></tr>

        </table>
    </div>
    
    <?
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showAdminPage($content) {
    ob_start();
    
    $wo = new WarungOptions();
    // check rights
    if (current_user_can("administrator")) {

        // get page
        $thePage = $_REQUEST["adm_page"];

        if (!empty($thePage)) {
            if ($thePage == "order")
                echo wrg_showAdminOrderPage();
            else if ($thePage == "order_update")
                echo wrg_showAdminOrderUpdatePage();
            else if ($thePage == "order_status_log")
                echo wrg_showAdminOrderLogPage();
            else if ($thePage == "order_add")
                echo wrg_showAdminOrderAddPage();
            else if ($thePage == "order_add_from_text")
                echo wrg_showAdminOrderAddFromTextPage();
            else if ($thePage == "order_send_mail") 
                echo wrg_showAdminOrderSendMailPage();
            else if ($thePage == "order_copy") 
                echo wrg_showAdminOrderCopyPage();
            else if ($thePage == "product_search") 
                echo wrg_jsonProducts();
            else if ($thePage == "city_search") 
                echo wrg_jsonCity();
            else if ($thePage == "cart_show") 
                echo wrg_htmlGetCart();
            
        } else {
            // default
            // show order
            echo wrg_showAdminOrderPage();
        }
        
    } else {
        // login page
        ?>
    <form action="<?php echo get_bloginfo('home'); ?>/wp-login.php" method="POST" class="well span3">
        <input name="log" type="text" placeholder="Username"/></p>
        <input name="pwd" type="password" placeholder="Password"/></p>
        <div class="form-actions">
            <input class="btn btn-primary" name="wp-submit" type="submit" value="Submit" />
        </div>

        <input type="hidden" name="redirect_to" value="<?=$wo->getAdminPageURL()?>" />
        <input type="hidden" name="testcookie" value="1" />

    </form>
        <?php
    }
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

?>
