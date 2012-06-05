<?php


interface IKasir2 {
    function getSummary($items, $user, $destination);
}

class WarungKasir2 implements IKasir2 {
    
    public static $ORDER_INFO_KEY = 'warung.shipping';
    
    private $DISCOUNT_MORE_THAN3;
    private $DISCOUNT_RESELLER;
    private $origin;
    private $freeShippingDest;
    
    public function __construct($discountMoreThan3=0, $discountReseller=8000, $origin='jakarta', 
            $freeShippingDest = array('jakarta'=>1.5, 'bogor'=>1.5, 'depok'=>1.5 ,'tangerang'=>1.5,'bekasi'=>1.5)) {
        
        $this->DISCOUNT_MORE_THAN3 = $discountMoreThan3;
        $this->DISCOUNT_RESELLER = $discountReseller;
        $this->origin = $origin;
        $this->freeShippingDest = $freeShippingDest;
    }
    
    public function getSummary($items, $user, $destination) {
        // ##### CALCULATE BASIC summary like: total price, total item, total weight
        $summary = $this->getItemsSummary($items);
        
        // ##### CALCULATE DISCOUNT
        if ($this->isReseller($user)) {
            // potong 8rb
            $summary["discount"] = $summary["totalItems"] * $this->DISCOUNT_RESELLER;
        } else if ($summary["totalItems"] >= 3 && $this->DISCOUNT_MORE_THAN3 > 0) {
            // diskon 5rb, kalo jumlah item > 3 
            // set total discount
            $summary["discount"] = $summary["totalItems"] * $this->DISCOUNT_MORE_THAN3;
        } else {
            // no diskon
        }
        
        // ##### CALCULATE SHIPPING
        // group by shipping
        $bags = $this->getTotalWeightByShipping($items);
        
//        error_log("user:".var_export($user,true));
//        error_log("destination:".var_export($destination,true));
//        error_log("bags:".var_export($bags,true));
        
        // count total shipping price
        if (!empty ($bags)) {
            $shippingErrors = array();
            $summary["shipping"]=$this->getTotalShipping($bags, $destination, $shippingErrors);
            
            if (!empty($shippingErrors)) {
                $summary["shipping.error"] = $shippingErrors;
            }
                
        }
        
//        if (sizeof($bags) == 1) {
//            $summary["shipping.name"] = key($bags);
//        }
        
        // calculate all
        $this->countGrandTotalPrice(&$summary);
        
        return $summary;
        
    }
    
    
    // ### Summarize
    private function sumItems(&$item, $key, &$summary) {
        $summary["totalItems"] += $item->quantity;
        $summary["totalPrice"] += $item->price * $item->quantity;
        $summary["totalWeight"] += $item->weight * $item->quantity;
    }
    
    public function getItemsSummary($items) {

        $summary = array("totalItems"=>0, "totalPrice"=>0, "totalWeight"=>0);
        
        if (!empty($items)) {
            array_walk($items, array(&$this,'sumItems'), &$summary);
        }
        
        return $summary;
    }
    
    public function countGrandTotalPrice(&$summary) {
        
        // get totalPrice
        if ( ! isset($summary["totalPrice.grand"])) {
            if ( isset ($summary["totalPrice"])) {
                $summary["totalPrice.grand"] = $summary["totalPrice"];
            } else {
                $summary["totalPrice.grand"] = 0;
            }
        }
        
        // add discount
        if (isset ($summary["discount"])) {
            $summary["totalPrice.grand"] -= $summary["discount"]; 
            $summary["totalPrice.discount"] = $summary["totalPrice.grand"]; 
        }
        
        // add shipping
        if (isset ($summary["shipping"])) {
            $summary["totalPrice.grand"] += $summary["shipping"]; 
        }
    }
    
    // reseller
    private function isReseller($user) {
        return $user === 'hendra';
    }
    
    // ### Seperate Shipping
    public function getTotalWeightByShipping($items) {
        // validation
        if (empty($items)) {
            return $items;
        }
        
        $bags = array();
        

        $defaultService = GenericShippingService::getDefaultService();
        
        // start separating
        foreach ($items as $item) {
            
            // get shipping name
            $shippingName = "__default";
            if ($item->shipping == 9999 || (empty($item->shipping) && $defaultService->id == 9999) ) {
                $shippingName = "__cheapest";
            } else if (!empty($item->shipping) ) {
                $shippingName = $item->shipping;
            } 
                
            // check shipping bag is empty
            if (empty($bags[$shippingName])) {
                // add new entry
                $bags[$shippingName] = array(
                            "totalWeight"=>$item->quantity*$item->weight,
                            "totalQuantity"=>$item->quantity
                        );
            } else {
                // append
                $prevArr = $bags[$shippingName];
                $bags[$shippingName] = array(
                            "totalWeight"=>$prevArr["totalWeight"]+($item->quantity*$item->weight),
                            "totalQuantity"=>$prevArr["totalQuantity"]+$item->quantity
                        );
                
            } 
        }
        
        return $bags;
        
    }
    
    public function getTotalShipping($bags, $destination, &$errors=array()) {
        if (empty($bags) || ! is_array($bags)) {
            return 0;
        }
        
        if (empty($destination)) {
            return 0;
        }

        $totalShippingPrice = 0;
        
        foreach ($bags as $shipping => $info) {
            
            $bagItemsQtt = $info["totalQuantity"];
            $bagTotalWeight = $info["totalWeight"];
            
            // FREE SHIPPING BY DEST & Weight
            if (!empty($this->freeShippingDest) && !empty ($this->freeShippingDest[$destination])){
                $discWeight = $bagItemsQtt * $this->freeShippingDest[$destination];
                if ($bagTotalWeight > $discWeight) {
                    $bagTotalWeight -= $discWeight;
                } else {
                    $bagTotalWeight = 0;
                }
            }
            
            // free shipping?
            if ($bagTotalWeight == 0) {
                return 0;
            }
            
            //rounding?
            if ($bagTotalWeight - floor($bagTotalWeight) > .1) {
                $bagTotalWeight = ceil($bagTotalWeight);
            }
            
            if($shipping=="__default") {
                
//                error_log("default");
                
                // find with default service
                
                $r = GenericShippingService::getDefaultService();
                if ($r) {
                    $s = new GenericShippingService($r->id, $this->origin);
                    $sprice = $s->getPrice($destination, $bagTotalWeight);
                    if (!empty($sprice)) {
                        $totalShippingPrice += $sprice;
                    } else {
                        $errors[] = "Shipping not available in '".$s->getServiceName()."' for '".$destination."'"; 
                    }
                } else {
                    $errors[] = "No shipping service available";
                }
                
            } else if ($shipping == "__cheapest") {
                $cheapestPrice = GenericShippingService::getCheapestPrice($this->origin, $destination, $bagTotalWeight);
                
//                error_log('cheapest price: '.$cheapestPrice);
                
                if (!empty($cheapestPrice)) {
                    $totalShippingPrice += $cheapestPrice;
                } else {
                    $errors[] = "Shipping not available for '".$destination."'"; 
                }
            } else {
                
//                error_log("by id: ".$shipping);
                
                $r = GenericShippingService::getServiceById($shipping);
                // TODO calculate min weight
                if ($r->min_weight > $bagTotalWeight) {
                    $r = GenericShippingService::getDefaultService();
                }
                
                if ($r) {
                    // count 
                    $s = new GenericShippingService($r->id, $this->origin);
                    $sprice = $s->getPrice($destination, $bagTotalWeight);
                    if (!empty($sprice)) {
                        $totalShippingPrice += $sprice;
                    } else {
                        $errors[] = "Shipping not available in '".$s->getServiceName()."' for '".$destination."'"; 
                    }
                    
                    // use default shipping
                }
            }
            
        } // e.loop
        
        return $totalShippingPrice;
    }
    
    
    // ================= user info ===================
    function saveUserInfo($userInfo) {
        $tmp = (object)array(
            'email' => $userInfo->email,
            'phone' => $userInfo->phone,
            'name' => $userInfo->name,
            'address' => $userInfo->address,
            'city' => $userInfo->city,
            'additionalInfo' => $userInfo->additionalInfo,
            'postalCode'=> $userInfo->postalCode,
            'paymentMethod' => $userInfo->paymentMethod
        );

        $_SESSION[WarungKasir2::$ORDER_INFO_KEY] = serialize($tmp);
        setcookie(WarungKasir2::$ORDER_INFO_KEY, serialize($tmp), time() + 60 * 60 * 24 * 30); // save 1 month
        
    }

    function getSavedUserInfo() {

        $tmp_info = (object)array(
            'email' => '',
            'phone' => '',
            'name' => '',
            'address' => '',
            'city' => '',
            'additionalInfo' => '',
            'postalCode'=>'',
            'paymentMethod'=>''
        );

        if (isset($_COOKIE[WarungKasir2::$ORDER_INFO_KEY])) {
            $tmp_info = unserialize(stripslashes($_COOKIE[WarungKasir2::$ORDER_INFO_KEY]));
        } else if (isset($_SESSION[WarungKasir2::$ORDER_INFO_KEY])) {
            $tmp_info = unserialize(stripslashes($_SESSION[WarungKasir2::$ORDER_INFO_KEY]));
        }

        
        return $tmp_info;
    }
    
}

?>
