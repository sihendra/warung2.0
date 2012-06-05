<?php

include_once('../../../../wp-config.php');
include_once('../../../../wp-load.php');
include_once('../../../../wp-includes/wp-db.php');
        
require_once 'shipping.php';
require_once 'kasir.php';


function kasirTest() {
    $items = array(
//        (object)array("name"=>"Sprei 1","price"=>10000,"quantity"=>3,"weight"=>1.3,"shipping"=>"jne - reguler"),
        (object)array("name"=>"Sprei 2","price"=>15000,"quantity"=>1,"weight"=>1,"shipping"=>"jne - reguler")
//        (object)array("name"=>"Sprei 3","price"=>30000,"quantity"=>2,"weight"=>1.5,"shipping"=>"pandusiwi")
    );
    
    $w = new WarungKasir2();
    $sum = $w->getSummary($items,'djaka','jakarta');
    
    var_dump($sum);
    
}


function shippingTest() {
    $s = new GenericShippingService();
    
    var_dump($s->getServices());
    
    var_dump($s->getServiceByName('jne - reguler'));
}

kasirTest();
//shippingTest();
?>
