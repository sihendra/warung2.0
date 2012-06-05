<?php

class WarungOptions {

    public static $OPT_NAME = 'Warung_Options';
    private $options;
    private $shippingServices;
    private $cartService;
    private $kasirService;
    private $checkoutWizard;

    public function __construct() {
        $this->options = get_option(WarungOptions::$OPT_NAME);
    }

    // general options

    public function getOptions() {
        return $this->options;
    }

    public function getCurrency() {
        if (!empty($this->options)) {
            return $this->options['currency'];
        }
    }

    public function getWeightSign() {
        if (!empty($this->options)) {
            return $this->options['weight_sign'];
        }
    }

    public function getAddToCartText() {
        if (!empty($this->options)) {
            return $this->options['add_to_cart'];
        }
    }

    public function getCheckoutPageId() {
        if (!empty($this->options)) {
            return $this->options['checkout_page'];
        }
    }

    public function getCheckoutURL() {
        if (!empty($this->options)) {
            return get_permalink($this->getCheckoutPageId());
        }
    }

    public function getShippingSimPageId() {
        if (!empty($this->options)) {
            return $this->options['shipping_sim_page'];
        }
    }

    public function getShippingSimURL() {
        if (!empty($this->options)) {
            return get_permalink($this->getShippingSimPageId());
        }
    }
    
    public function getAdminPageId() {
        if (!empty($this->options)) {
            return $this->options['admin_page'];
        }
    }
    
    public function getAdminPageURL() {
        if (!empty($this->options)) {
            return get_permalink($this->getAdminPageId());
        }
    }

    public function getHomeURL() {
        return get_option("home");
    }

    /**
     * Get predefined global options set from admin page
     * @return Array of product options. format array { "name" => object{name, value, txt} }
     */
    public function getGlobalProductOptions() {
        if (!empty($this->options)) {
            return $this->options['prod_options'];
        }
    }


}

?>
