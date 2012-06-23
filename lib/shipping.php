<?php

DEFINE('WRG_TBL_SHIPPING_SERVICE','wrg_shipping_service');
DEFINE('WRG_TBL_SHIPPING_PRICE','wrg_shipping_price');

/********************
 * Shipping Service
 ********************/
interface IShippingService2 {
    /**
     * Get all reachable destination of this shipping service 
     */
    function getDestinations();
    
    /**
     * Get the price by dest and weight
     * @param $dest string the city name
     * @param $weight float the weight
     */
    function getPrice($dest, $weight);
}

abstract class AbstractShippingService implements IShippingService2 {
    
    protected $tblService;
    protected $tblPrice;
    protected $serviceId;
    protected $origin;
    protected $serviceName;

    public function __construct($serviceId, $origin) {
        global $wpdb;
        
        $this->serviceId = $serviceId;
        $this->origin = $origin;
        
        $this->tblService = $wpdb->prefix . WRG_TBL_SHIPPING_SERVICE;
        $this->tblPrice = $wpdb->prefix . WRG_TBL_SHIPPING_PRICE;
    }
    
    // =================== STATIC =====================
    
    /**
     *
     * @global type $wpdb
     * @return type 
     */
    public static function getAllServices() {
        global $wpdb;

        $tbl = $wpdb->prefix . WRG_TBL_SHIPPING_SERVICE;
        
        $sql = $wpdb->prepare(
                "SELECT id, name, is_default
                   FROM $tbl ORDER BY is_default DESC, name ASC
                ");
                
        $result = $wpdb->get_results($sql);

        return $result;        
    }
    
    public static function getAllDestinations() {
        global $wpdb;

        $tbl = $wpdb->prefix . WRG_TBL_SHIPPING_PRICE;
        
        $sql = $wpdb->prepare(
                "SELECT DISTINCT(destination) destination
                   FROM $tbl
                ORDER BY destination");
                
        $result = $wpdb->get_results($sql);
        
        $ret = array();
        
        foreach($result as $row) {
            $ret[$row->destination] = ucwords($row->destination);
        }
        
        return $ret;        
        
    }
    
    public static function searchAllDestinations($query) {
        global $wpdb;

        $tbl = $wpdb->prefix . WRG_TBL_SHIPPING_PRICE;
        
        $sql = $wpdb->prepare(
                "SELECT destination FROM (
                    SELECT DISTINCT(destination) destination
                     FROM $tbl
                ) a WHERE destination like %s", $query.'%');
                
        $result = $wpdb->get_results($sql);
        
        $ret = array();
        
        foreach($result as $row) {
            $ret[$row->destination] = ucwords($row->destination);
        }
        
        return $ret;        
        
    }
    
    public static function getServiceByName($name) {
        global $wpdb;

        $tbl = $wpdb->prefix . WRG_TBL_SHIPPING_SERVICE;
        
        $sql = $wpdb->prepare(
                "SELECT id, name, is_default
                   FROM $tbl
                  WHERE upper(name) = %s
                ", strtoupper($name));
                
        $result = $wpdb->get_results($sql);
        
        if ($result !== FALSE) {
            return $result[0];
        }

        return $result;     
    }
    
    public static function getServiceById($id) {
        global $wpdb;

        $tbl = $wpdb->prefix . WRG_TBL_SHIPPING_SERVICE;
        
        $sql = $wpdb->prepare(
                "SELECT id, name, is_default
                   FROM $tbl
                  WHERE id = %d
                ", $id);
                
        $result = $wpdb->get_results($sql);
        
        if ($result !== FALSE) {
            return $result[0];
        }

        return $result;     
    }
    
    public static function getDefaultService() {
        global $wpdb;

        $tbl = $wpdb->prefix . WRG_TBL_SHIPPING_SERVICE;
        
        $sql = $wpdb->prepare(
                "SELECT id, name, is_default
                   FROM $tbl
                  WHERE is_default = 1
                  LIMIT 1");
                
        $result = $wpdb->get_results($sql);
        
        if ($result !== FALSE) {
            return $result[0];
        }

        return $result;     
    }
    
    /**
     * Get cheapest price from all shipping service
     * The cheapest shipping service will be saved in the $shippingService parameter (by ref)
     * 
     * @param type $origin
     * @param type $destination
     * @param type $weight
     * @param type $shippingService the cheapest shipping service array with key "id" and "name"
     * @return int 
     */
    public static function getCheapestPrice($origin, $destination, $weight, &$shippingService=array()) {
        $services = self::getAllServices();
        $minPrice = -1;
        foreach($services as $service) {
            
            // skip predefined service
            if ($service->id >= 9999) continue;
            
            $s = new GenericShippingService($service->id, $origin);
            $price = $s->getPrice($destination, $weight);
            
            if ($price != NULL && $price >= 0) {
                if ($minPrice == -1) {
                    $minPrice = $price;
                    
                    // set shipping info
                    $shippingService["id"] = $s->getServiceId();
                    $shippingService["name"] = $s->getServiceName();
                } else {
                    // min price found, set shipping info
                    if ($price < $minPrice) {
                        $shippingService["id"] = $s->getServiceId();
                        $shippingService["name"] = $s->getServiceName();
                    }
                    $minPrice = min(array($minPrice, $price));
                }
            }
        }
        
        if ($minPrice == -1) {
            $minPrice = 0;
        }
        
        return $minPrice;
    }
    
    public static function getDefaultServicePrice($origin, $destination, $weight) {
        $services = self::getDefaultService();
        $price = -1;
        foreach($services as $service) {
            $s = new GenericShippingService($service->id, $origin);
            $price = $s->getPrice($destination, $weight);
        }
        
        return $price;
    }

    public static function setDefaultService($serviceId) {
        global $wpdb;

        $tbl = $wpdb->prefix . WRG_TBL_SHIPPING_SERVICE;
        
        
        $sql = $wpdb->prepare(
                "UPDATE $tbl SET is_default = 1 WHERE id = %d", $serviceId);
                
        $result = $wpdb->query($sql);
        
        if ($result) {
            
            $sql = $wpdb->prepare(
                "UPDATE $tbl SET is_default = 0 WHERE id <> %d", $serviceId);
        
            $result = $wpdb->query($sql);
            
            return $result;
        }

        return $result;     
    }
    
    // ===================== e.STATIC ==========================
    
    public function getAllOrigins($serviceId) {
        global $wpdb;

        $sql = $wpdb->prepare(
                "SELECT distinct(origin) origin
                   FROM $this->tblPrice
                  WHERE service_id = %d
                ", $serviceId);
                
        $result = $wpdb->get_results($sql);

        return $result;        
    }
    
    public function getDestinations() {
        global $wpdb;

        $sql = $wpdb->prepare(
                "SELECT distinct(destination) destination
                 FROM $this->tblPrice 
                WHERE service_id=%d 
                  AND origin = %s 
                ORDER BY destination", $this->serviceId, $this->origin);

        $result = $wpdb->get_results($sql);

        return $result;
        
    }
    
    public function getPrice($destination, $weight) {
        global $wpdb;

        $sql = $wpdb->prepare(
                "SELECT p.price
                 FROM $this->tblPrice p join $this->tblService s ON p.service_id = s.id
                WHERE p.service_id=%d 
                  AND p.origin = %s
                  AND p.destination = %s
                  AND p.min_weight <= %d", $this->serviceId, $this->origin, $destination, $weight);

//        error_log($sql);
        
        $result = $wpdb->get_var($sql);

        if ($result) {
            return $result * $weight;
        }
        
        return $result;
    }
    
    public function getServiceId() {
        return $this->serviceId;
    }
    
    public function getOrigin() {
        return $this->origin;
    }
    
    public function setServiceId($serviceId) {
        $this->serviceId = $serviceId;
    }
    
    public function setOrigin($origin) {
        $this->origin = $origin;
    }
    
    public function getServiceName () {
        if (!empty($this->serviceId)) {
            $res = $this->getServiceById($this->serviceId);
            if ($res) {
                return $res->name;
            }
        }
        
        return null;
    }
}

class GenericShippingService extends AbstractShippingService {
    public function __construct($serviceId, $origin) {
        parent::__construct($serviceId, $origin);
    }
}

?>
