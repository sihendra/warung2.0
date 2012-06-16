<?php
/**
 * Description of IOrder
 *
 * @author hendra
 */
interface IOrderService {
    /**
     * Insert new Order
     * @param Order $order
     * @return int|false if error will return false
     */
    function putOrder($order);
    
    function copyOrder($orderId);

    /**
     * Update order
     * @param Order $order
     * @return int|false if error will return false
     */
    function updateStatus($orderId, $status);

    function updateDeliveryNumber($orderId, $delivNum);


    function getOrderById($orderId);
    function getAllOrders();
    function getAllStatus();
    
    function getOrderStat();
}

/**
 * Description of OrderService
 *
 * @author hendra
 */
class OrderService implements IOrderService {

    private $orderTable;
    private $orderItemsTable;
    private $orderShippingTable;
    private $orderStatusTable;
    private $orderStatusHistoryTable;

    // update statuses
    public static $STATUS_NEW = 1;
    public static $STATUS_PAID = 2;
    public static $STATUS_PROCESS = 3;
    public static $STATUS_SENT = 4;
    public static $STATUS_CANCEL = 5;

    public function __construct() {
        global $wpdb;
        
        $this->orderTable = $wpdb->prefix . "wrg_order";
        $this->orderItemsTable = $wpdb->prefix . "wrg_order_items";
        $this->orderShippingTable = $wpdb->prefix . "wrg_order_shipping";
        $this->orderStatusTable = $wpdb->prefix."wrg_order_status";
        $this->orderStatusHistoryTable = $wpdb->prefix . "wrg_order_status_history";
    }

    //put your code here
    public function getAllOrders($showPerPage=10, $page=1, $orderBy='id DESC') {
        global $wpdb;

        $ret = array();
        $rows = array();
        
        if ($page <= 0) {
            $page = 1;
        }

        // count all
        $sql = "SELECT count(*)
                  FROM $this->orderTable";
        $totalRow = $wpdb->get_var($wpdb->prepare($sql));
        $totalPage = ceil($totalRow / $showPerPage);
        $offset = ($page-1)*($showPerPage);
        if ($page==$totalPage && $totalRow%$showPerPage > 0) {
            $showPerPage = $totalRow%$showPerPage;
        }

        $sql = $wpdb->prepare("SELECT id, dtcreated, status_id, dtstatus, total_price, shipping_price, delivery_number
                  FROM $this->orderTable
                 ORDER BY $orderBy LIMIT %d,%d", $offset, $showPerPage);

        $result = $wpdb->get_results($sql);

        if($result) {

            // loop through order table
            foreach($result as $row) {

                // get order
                $order = (object) array();
                $order->id = $row->id;
                $order->dtcreated = $row->dtcreated;
                $order->statusId = $row->status_id;
                $order->dtstatus = $row->dtstatus;
                $order->totalPrice = $row->total_price;
                $order->shippingPrice = $row->shipping_price;
                $order->deliveryNumber = $row->delivery_number;

                // get shipping/buyer info
                $sql = $wpdb->prepare(
                        "SELECT name, email, mobile_phone, phone, address, city, state, country, additional_info
                          FROM $this->orderShippingTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);

                if($r2) {
                    $s = $r2[0];

                    $i = (object) array(
                        "name" => $s->name, 
                        "email" => $s->email, 
                        "mobilePhone" => $s->mobile_phone, 
                        "phone" => $s->phone, 
                        "address" => $s->address, 
                        "city" => $s->city, 
                        "country" => $s->country, 
                        "state" => $s->state,
                        "additionalInfo" => $s->additional_info
                    );
                    
                    $order->shippingInfo=$i;

                }

                // get items
                $sql = $wpdb->prepare(
                        "SELECT item_id, name, quantity, weight, price
                          FROM $this->orderItemsTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);
                if($r2) {
                    $items = array();
                    foreach($r2 as $row2) {
                        $i = (object) array(
                            "id"=>$row2->item_id, 
                            "name"=>$row2->name, 
                            "price"=>$row2->price, 
                            "weight"=>$row2->weight, 
                            "quantity"=>$row2->quantity, 
                            );
                        array_push($items, $i);
                    }

                    if (!empty($items)) {
                        $order->items=$items;
                    }
                }

                array_push($rows, $order);
            }
        }

        $ret['data'] = $rows;
        $ret['totalRow'] = $totalRow;
        $ret['totalPage'] = $totalPage;
        $ret['offset'] = $offset;
        $ret['showPerPage'] = $showPerPage;
        $ret['page'] = $page;

        return $ret;
    }

    public function putOrder($order) {
        $ret = false;
        
        global $wpdb;
        
        if (isset($order->totalPrice) ) {

            $sql = $wpdb->prepare("
                    INSERT INTO $this->orderTable (status_id, dtstatus, total_price, shipping_price, delivery_number, total_weight, shipping_services, payment_method)
                    VALUES (%s, NOW(), %d, %d, %s, %f, %s, %s)",
                    $order->statusId,
                    $order->totalPrice,
                    $order->shippingPrice,
                    $order->deliveryNumber,
                    $order->shippingWeight,
                    $order->shippingServices,
                    $order->paymentMethod
                    );

            if (($ret = $wpdb->query($sql)) > 0) {

                $orderId = $wpdb->insert_id;
                $ret = $orderId;

                // insert into shipping info
                if (isset($orderId) && isset($order->shippingInfo)) {
                    $ts = $order->shippingInfo;

                    $sql = $wpdb->prepare("
                        INSERT INTO $this->orderShippingTable (order_id, name, email, mobile_phone, phone, address, city, state, country, additional_info, postal_code)
                        VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d)",
                        $orderId,
                        $ts->name,
                        $ts->email,
                        $ts->mobilePhone,
                        $ts->phone,
                        $ts->address,
                        $ts->city,
                        $ts->state,
                        $ts->country,
                        $ts->additionalInfo,
                        $ts->postalCode
                    );

                    $wpdb->query($sql);
                }

                // insert into items
                if (isset($orderId) && isset($order->items)) {
                    $ti = $order->items;

                    if (is_array($ti)) {
                        foreach ($ti as $i) {
                            $sql = $wpdb->prepare("
                                INSERT INTO $this->orderItemsTable (order_id, item_id, name, quantity, weight, price)
                                VALUES (%d, %s, %s, %d, %f, %d)",
                                $orderId,
                                $i->productId,
                                $i->name,
                                $i->quantity,
                                $i->weight,
                                $i->price
                            );

                            $wpdb->query($sql);
                        }
                    }
                }
                
                // insert into status history
                $this->addStatusHistory($orderId, $order->statusId);

            } else {
//                error_log('error while inserting order');
            }

        } else {
//            error_log('invalid order data');
        }

        return $ret;
        
    }
    
    public function copyOrder($orderId) {
        $ret = false;
        
        global $wpdb;
        
        if (!empty($orderId) ) {
            $oldOrder = $this->getOrderById($orderId);
            
            if ($oldOrder) {
                // do copy
                $ret = $this->putOrder($oldOrder);
            } else {
                // not found
            }
        } else {
            // not found
        }

        return $ret;
        
    }

    public function updateStatus($orderId, $statusId) {
        global $wpdb;

        
        if ($this->isValidNewStatus($oldStatus, $statusId)) {
                
            $params = array($statusId);
            array_push($params, $orderId);

            $sql = $wpdb->prepare("
                    UPDATE $this->orderTable
                       SET status_id=%s, dtstatus = NOW() $others
                     WHERE id=%d",
                    $params
                    );

            $ret = $wpdb->query($sql);
            
            
            
            // insert into status history
            $this->addStatusHistory($orderId, $statusId);
            
            return $ret;
            
        }

        return false;

    }

    private function isValidNewStatus($oldStatus, $newStatus) {

        return true;
        /*
        if ($oldStatus == self::$STATUS_ORDERED) {
            if ($newStatus == self::$STATUS_PAYMENT_VERIFIED || $newStatus == self::$STATUS_PAYMENT_NOT_VERIFIED || $newStatus == self::$STATUS_CANCELED) {
                return true;
            }
        } else if ($oldStatus == self::$STATUS_CANCELED) {
            return false;
        }
        return false;
        */
    }

    /**
     *
     * @global wpdb $wpdb
     * @param int $orderId
     * @return false|Order
     */
    public function getOrderById($orderId) {
        global $wpdb;

        $ret = false;

        $sql = $wpdb->prepare("SELECT id, dtcreated, status_id, dtstatus, total_price, shipping_price, delivery_number, shipping_services, payment_method
                  FROM $this->orderTable
                 WHERE id = %d", $orderId);

        $result = $wpdb->get_results($sql);

        if($result) {

            // loop through result
            foreach($result as $row) {

                $order = (object)array();
                $order->id = $row->id;
                $order->dtcreated = $row->dtcreated;
                $order->statusId = $row->status_id;
                $order->dtstatus = $row->dtstatus;
                $order->totalPrice = $row->total_price;
                $order->shippingPrice = $row->shipping_price;
                $order->deliveryNumber = $row->delivery_number;
                $order->shippingServices = $row->shipping_services;
                $order->paymentMethod = $row->payment_method;

                // get shipping/buyer info
                $sql = $wpdb->prepare(
                        "SELECT name, email, mobile_phone, phone, address, city, state, country, additional_info, postal_code
                          FROM $this->orderShippingTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);

                if($r2) {
                    $s = $r2[0];

                    $i = (object) array(
                        "name" => $s->name, 
                        "email" => $s->email, 
                        "mobilePhone" => $s->mobile_phone, 
                        "phone" => $s->phone, 
                        "address" => $s->address, 
                        "city" => $s->city, 
                        "country" => $s->country, 
                        "state" => $s->state,
                        "additionalInfo" => $s->additional_info,
                        "postalCode" => $s->postal_code
                    );
                    
                    $order->shippingInfo=$i;

                }

                // get items
                $sql = $wpdb->prepare(
                        "SELECT item_id, name, quantity, weight, price
                          FROM $this->orderItemsTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);
                if($r2) {
                    $items = array();
                    foreach($r2 as $row2) {
                        $i = (object) array(
                            "id"=>$row2->item_id, 
                            "name"=>$row2->name, 
                            "price"=>$row2->price, 
                            "weight"=>$row2->weight, 
                            "quantity"=>$row2->quantity, 
                            );
                        array_push($items, $i);
                    }

                    if (!empty($items)) {
                        $order->items=$items;
                    }
                }

                $ret = $order;

            }
        }

        return $ret;
    }
    
    public function getOrderByStatus($statusId, $showNotificationEntries=false, $page=1, $showPerPage=10, $orderBy='id DESC') {
        global $wpdb;

        $ret = array();
        $rows = array();
        
        if ($page <= 0) {
            $page = 1;
        }

        // count all
        $sql = "SELECT count(*)
                  FROM $this->orderTable WHERE status_id = $statusId";
        $totalRow = $wpdb->get_var($wpdb->prepare($sql));
        $totalPage = ceil($totalRow / $showPerPage);
        $offset = ($page-1)*($showPerPage);
        if ($page==$totalPage && $totalRow%$showPerPage > 0) {
            $showPerPage = $totalRow%$showPerPage;
        }

        $sql = $wpdb->prepare(
                "SELECT id, dtcreated, status_id, dtstatus, total_price, shipping_price, delivery_number, shipping_services, payment_method, 
                        IF( (status_id <> 1 AND dtstatus <= DATE_ADD(CURRENT_DATE, INTERVAL-3 DAY)) OR status_id = 1,1,0) AS attn
                  FROM $this->orderTable
                 WHERE status_id = %d
                 ORDER BY $orderBy LIMIT %d,%d", $statusId, $offset, $showPerPage);

        if ($showNotificationEntries) {
            $sql = $wpdb->prepare(
                "SELECT id, dtcreated, status_id, dtstatus, total_price, shipping_price, delivery_number, shipping_services, payment_method, 
                        IF( (status_id <> 1 AND dtstatus <= DATE_ADD(CURRENT_DATE, INTERVAL-3 DAY)) OR status_id = 1,1,0) AS attn
                  FROM $this->orderTable
                 WHERE (status_id = %d AND dtstatus <= DATE_ADD(CURRENT_DATE, INTERVAL-3 DAY)) OR (%d = 1 AND status_id = 1)
                 ORDER BY $orderBy LIMIT %d,%d", $statusId, $statusId, $offset, $showPerPage);
        }
        
        $result = $wpdb->get_results($sql);

        if($result) {

            // loop through order table
            foreach($result as $row) {

                // get order
                $order = (object) array();
                $order->id = $row->id;
                $order->dtcreated = $row->dtcreated;
                $order->statusId = $row->status_id;
                $order->dtstatus = $row->dtstatus;
                $order->totalPrice = $row->total_price;
                $order->shippingPrice = $row->shipping_price;
                $order->deliveryNumber = $row->delivery_number;
                $order->requireAttention = $row->attn;
                $order->shippingServices = $row->shipping_services;
                $order->paymentMethod = $row->payment_method;
                
                // get shipping/buyer info
                $sql = $wpdb->prepare(
                        "SELECT name, email, mobile_phone, phone, address, city, state, country, additional_info, postal_code
                          FROM $this->orderShippingTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);

                if($r2) {
                    $s = $r2[0];

                    $i = (object) array(
                        "name" => $s->name, 
                        "email" => $s->email, 
                        "mobilePhone" => $s->mobile_phone, 
                        "phone" => $s->phone, 
                        "address" => $s->address, 
                        "city" => $s->city, 
                        "country" => $s->country, 
                        "state" => $s->state,
                        "additionalInfo" => $s->additional_info,
                        "postalCode" => $s->postal_code
                    );
                    
                    $order->shippingInfo=$i;

                }

                // get items
                $sql = $wpdb->prepare(
                        "SELECT item_id, name, quantity, weight, price
                          FROM $this->orderItemsTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);
                if($r2) {
                    $items = array();
                    foreach($r2 as $row2) {
                        $i = (object) array(
                            "id"=>$row2->item_id, 
                            "name"=>$row2->name, 
                            "price"=>$row2->price, 
                            "weight"=>$row2->weight, 
                            "quantity"=>$row2->quantity, 
                            );
                        array_push($items, $i);
                    }

                    if (!empty($items)) {
                        $order->items=$items;
                    }
                }

                array_push($rows, $order);
            }
        }

        $ret['data'] = $rows;
        $ret['totalRow'] = $totalRow;
        $ret['totalPage'] = $totalPage;
        $ret['offset'] = $offset;
        $ret['showPerPage'] = $showPerPage;
        $ret['page'] = $page;

        return $ret;
    }
    
    public function searchOrder($query, $page=1, $showPerPage=10, $orderBy='o.id DESC') {
        global $wpdb;

        $ret = array();
        $rows = array();
        
        if ($page <= 0) {
            $page = 1;
        }

        // count all
        $sql = "SELECT count(*)
                  FROM $this->orderTable o JOIN $this->orderShippingTable s ON o.id = s.order_id
                 WHERE s.name LIKE %s OR s.mobile_phone LIKE %s OR s.city LIKE %s";
        
        $totalRow = $wpdb->get_var($wpdb->prepare($sql, $query,$query,$query));
        $totalPage = ceil($totalRow / $showPerPage);
        $offset = ($page-1)*($showPerPage);
        if ($page==$totalPage && $totalRow%$showPerPage > 0) {
            $showPerPage = $totalRow%$showPerPage;
        }
        
        $sql = $wpdb->prepare(
                "SELECT o.id, o.dtcreated, o.status_id, o.dtstatus, o.total_price, o.shipping_price, o.delivery_number, o.shipping_services, o.payment_method,
                        st.description AS status_name,
                        s.name, s.email, s.mobile_phone, s.phone, s.address, s.city, s.state, s.country, s.additional_info, s.postal_code
                  FROM $this->orderTable o JOIN $this->orderShippingTable s ON o.id = s.order_id JOIN $this->orderStatusTable st ON st.id = o.status_id
                 WHERE s.name LIKE %s OR s.mobile_phone LIKE %s OR s.city LIKE %s
                 ORDER BY $orderBy LIMIT %d,%d", $query,$query,$query, $offset, $showPerPage);

        $result = $wpdb->get_results($sql);

        if($result) {

            // loop through order table
            foreach($result as $row) {

                // get order
                $order = (object) array();
                $order->id = $row->id;
                $order->dtcreated = $row->dtcreated;
                $order->statusId = $row->status_id;
                $order->dtstatus = $row->dtstatus;
                $order->totalPrice = $row->total_price;
                $order->shippingPrice = $row->shipping_price;
                $order->deliveryNumber = $row->delivery_number;
                $order->statusName = $row->status_name;
                $order->shippingServices = $row->shipping_services;
                $order->paymentMethod= $row->payment_method;

                // get shipping/buyer info
                $i = (object) array(
                    "name" => $row->name, 
                    "email" => $row->email, 
                    "mobilePhone" => $row->mobile_phone, 
                    "phone" => $row->phone, 
                    "address" => $row->address, 
                    "city" => $row->city, 
                    "country" => $row->country, 
                    "state" => $row->state,
                    "additionalInfo" => $row->additional_info,
                    "postalCode" => $row->postalCode
                );

                $order->shippingInfo=$i;


                // get items
                $sql = $wpdb->prepare(
                        "SELECT item_id, name, quantity, weight, price
                          FROM $this->orderItemsTable
                        WHERE order_id=%d", $order->id);

                $r2 = $wpdb->get_results($sql);
                if($r2) {
                    $items = array();
                    foreach($r2 as $row2) {
                        $i = (object) array(
                            "id"=>$row2->item_id, 
                            "name"=>$row2->name, 
                            "price"=>$row2->price, 
                            "weight"=>$row2->weight, 
                            "quantity"=>$row2->quantity, 
                            );
                        array_push($items, $i);
                    }

                    if (!empty($items)) {
                        $order->items=$items;
                    }
                }

                array_push($rows, $order);
            }
        }

        $ret['data'] = $rows;
        $ret['totalRow'] = $totalRow;
        $ret['totalPage'] = $totalPage;
        $ret['offset'] = $offset;
        $ret['showPerPage'] = $showPerPage;
        $ret['page'] = $page;

        return $ret;
    }
    
    public function updateDeliveryNumber($orderId, $delivNum) {
        global $wpdb;

        $sql = $wpdb->prepare("
                UPDATE $this->orderTable
                   SET delivery_number=%s
                 WHERE id=%d",
                $delivNum,
                $orderId
                );

        return $wpdb->query($sql);

    }

    public function getAllStatus() {
        
        global $wpdb;

        $ret = false;

        $sql = "
                SELECT id, description
                  FROM $this->orderStatusTable
                ORDER BY id
                ";
        
        $result = $wpdb->get_results($sql);

        if($result) {
            foreach ($result as $row) {
                $ret[$row->id] = $row->description;
            }
        }
        
        return $ret;
        
    }

    public function getOrderStat() {
        global $wpdb;

        $ret = false;

        $sql = "
                SELECT date_format(o.dtcreated, '%b') month, r.description status, sum(i.quantity) total 
                 FROM $this->orderTable o left join $this->orderItemsTable i on o.id = i.order_id
                 JOIN $this->orderStatusTable r on o.status = r.id
                where o.dtcreated >= date_format(CURRENT_DATE, '%Y-01-01')
                group by date_format(o.dtcreated, '%b'), o.status
                order by o.status, o.dtcreated
                ";

        $result = $wpdb->get_results($sql);

        if($result) {
            $ret = $result;
        }
        
        return $ret;
    }
    
    public function getStatusNotification() {
        global $wpdb;
        
        $ret = false;
        
        $sql = 
            "SELECT  
                SUM(IF(status_id=1,total,0)) `new`, 
                SUM(IF(status_id=2,total,0)) `paid`, 
                SUM(IF(status_id=3,total,0)) `process`, 
                SUM(IF(status_id=4,total,0)) `sent`
               FROM (
                    SELECT s.id AS `status_id`, count(*) AS `total`
                        FROM $this->orderTable o JOIN $this->orderStatusTable s ON o.status_id = s.id
                    WHERE o.dtstatus <= DATE_ADD(CURRENT_DATE, INTERVAL -3 DAY)
                        OR (o.status_id = 1)
                    GROUP BY s.id, s.description
            ) a";

        $result = $wpdb->get_results($sql);

        if($result) {
            $ret = $result;
        }
        
        return $ret;
    }

    
    public function getStatusHistory($orderId) {
        
        global $wpdb;

        $ret = false;

        $sql = $wpdb->prepare("
                SELECT h.dtlog, s.description `status`
                  FROM $this->orderStatusHistoryTable h JOIN $this->orderStatusTable s ON h.status_id = s.id
                 WHERE h.order_id = %d
                ORDER BY dtlog desc",$orderId);
        
        $result = $wpdb->get_results($sql);

        if($result) {
            return $result;
        }
        
        return $ret;
        
    }
    
    public function addStatusHistory($orderId, $statusId) {
        
        global $wpdb;

        
        // insert into status history
        $sql = $wpdb->prepare("
            INSERT INTO $this->orderStatusHistoryTable (order_id, dtlog, status_id)
            VALUES (%d, NOW(), %d)",
            $orderId,
            $statusId
            );
        
//            error_log($sql);
                
        return $wpdb->query($sql);
    }

    
}
?>
