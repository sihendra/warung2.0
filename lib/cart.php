<?php
class WarungCart2 {

    private static $SESSION_NAME = 'warung.cart';
    private static $MAX_ITEM = 9999;
    
    public function &getItems() {
        $prevCart = $_SESSION[WarungCart2::$SESSION_NAME];
        if (!isset($prevCart) || empty($prevCart)) {
            $_SESSION[WarungCart2::$SESSION_NAME] = array();
        }
        return $_SESSION[WarungCart2::$SESSION_NAME];
    }
    
    function addItem($item, $count) {
        // guard
        if ($count > self::$MAX_ITEM) {
            $count = self::$MAX_ITEM;
        }
        
        $items  = &$this->getItems();
        $itemId = strval($item->cartId);
        $oldItem = $items[$itemId];
        if (isset($oldItem)) {
            // already exists, just update count                
            $oldItem->quantity += $count;

            if ($oldItem->quantity <= 0) {
                // this means delete entry
                unset($items[$itemId]);
            }
        } else if ($count > 0) {
            // add new
            $item->quantity = $count;
            $items[$itemId] = $item;
        } else {
            // dont add
        }
        
    }
    
    function removeItem($item) {
        $items  = &$this->getItems();
        $itemId = strval($item->cartId);
        $oldItem = $items[$itemId];
        if (isset($oldItem)) {
            unset($oldItem);
        }
    }

    function updateQuantity($cartId, $count) {
        $items  = &$this->getItems();
        $oldItem = &$items[$cartId];
        
        // guard
        if ($count > self::$MAX_ITEM) {
            $count = self::$MAX_ITEM;
        }
        if ($count < 0) {
            $count = 0;
        }
        
        if (isset($oldItem)) {
            if ($count == 0) {
                unset($items[$cartId]);
            } else {
                $oldItem->quantity = $count;
            }
        }
    }
    
    function getTotalItems() {
        $totalItems = 0;
        $items  = &$this->getItems();
        if ($items) {
            foreach($items as $item) {
                $totalItems += $item->quantity;
            }
        }
        
        return $totalItems;
    }
    
    function emptyCart() {
        unset($_SESSION[WarungCart2::$SESSION_NAME]);
    }
    
    function isEmpty() {
        return sizeof($this->getItems())==0;
    }
    
}
?>
