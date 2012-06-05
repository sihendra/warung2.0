<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WarungProduct
 *
 * @author hendra
 */
class WarungProduct {

    public static function getProductById($post_id, $calculateDiscount = true) {
        $ret = array();

        $product_code = get_post_meta($post_id, '_warung_product_code', true);
        $product_name = get_post_field('post_title', $post_id);
        $product_price = get_post_meta($post_id, '_warung_product_price', true);
        $product_weight = get_post_meta($post_id, '_warung_product_weight', true);
        $product_options_name = get_post_meta($post_id, '_warung_product_options', true);
        $product_thumbnail = get_post_meta($post_id, 'thumbnail', true);
        $product_weight_discount = get_post_meta($post_id, '_warung_product_weight_discount', true);
        $product_stock = get_post_meta($post_id, '_warung_product_stock', true);
        $product_show_stock = get_post_meta($post_id, '_warung_product_show_stock', true);
        $product_price_discount = get_post_meta($post_id, '_warung_product_price_discount', true);
        $product_shipping = get_post_meta($post_id, '_warung_product_shipping', true);

        $post = get_post($post_id);
        if (!empty($post) && empty($product_thumbnail)) {
            $dom = new DOMDocument();
            if (!empty($post->post_content) && $dom->loadHTML($post->post_content)) {
                $images = $dom->getElementsByTagName("img");
                foreach ($images as $img) {
                    $product_thumbnail = $img->getAttribute("src");
                    break;
                }
            }
        }

        if (!empty($product_code)) {
            $ret["id"] = $post_id;
            $ret["code"] = $product_code;
            $ret["name"] = $product_name;
            $ret["price"] = $product_price;
            $ret["weight"] = $product_weight;
            $ret["thumbnail"] = $product_thumbnail;
            $ret["stock"] = $product_stock;
            $ret["show_stock"] = $product_show_stock;
            $ret["shipping"] = $product_shipping;

            // check for discount
            if (!empty($product_weight_discount)) {
                if ($calculateDiscount) {
                    $ret["weight_discount"] = $product_weight_discount;
                    $ret["weight"] = max(array(0, $product_weight - $product_weight_discount));
                } else {
                    $ret["weight_discount"] = $product_weight_discount;
                }
            }
            if (!empty($product_price_discount)) {
                if ($calculateDiscount) {
                    $ret["price_discount"] = $product_price;
                    $ret["price"] = $product_price_discount;
                } else {
                    $ret["price_discount"] = $product_price_discount;
                }
            }

            if (!empty($product_options_name)) {
                $wo = new WarungOptions();

                $opts = $wo->getOptions();
                $prod_opts = $opts['prod_options'];

                if (!empty($prod_opts) && is_array($prod_opts)) {
                    foreach ($prod_opts as $k => $v) {
                        if ($v->name == $product_options_name) {
                            $ret["option_name"] = $product_options_name;
                            if (isset($v->value)) {
                                $ret["option_value"] = WarungUtils::parseJsonMultiline($v->value);
                            }
                            if (isset($v->txt)) {
                                $ret["option_text"] = $v->txt;
                            }
                        }
                    }
                }
            }
        }

        return $ret;
    }

    public static function search($query) {
        // The Query
        $the_query = new WP_Query( array('s'=>$query, 'meta_key' => '_warung_product_code') );

        // The Loop
        $res = array();
        while ( $the_query->have_posts() ) : $the_query->the_post();
            $product_options_name = get_post_meta(get_the_ID(), '_warung_product_options', true);
            $prod = (object) array("id"=>get_the_ID(),"text"=>get_the_title());
            

            if (!empty($product_options_name)) {
                // add options
                
                $wo = new WarungOptions();

                $opts = $wo->getOptions();
                $prod_opts = $opts['prod_options'];

                // get product options
                $options = array();
                if (!empty($prod_opts) && is_array($prod_opts)) {
                    foreach ($prod_opts as $k => $v) {
                        if ($v->name == $product_options_name) {
                            if (isset($v->value)) {
                                $opt_list = WarungUtils::parseJsonMultiline($v->value);
                                foreach($opt_list as $opt_val) {
                                    $prod = array("id"=>get_the_ID(),"text"=>get_the_title()." ".$opt_val->name, "product_option"=>$opt_val->id);
                                    $options[] = $prod;
                                }
                                
                            }
                        }
                    }
                }
                
                if (!empty($options)) {
                    $res = array_merge($res, $options);
                }
            } else {
                // no option
                $res[] = $prod;
            }
        endwhile;

        // Reset Post Data
        wp_reset_postdata();
        
        echo json_encode($res);
    }
}

?>
