<?php

function wrg_showAdminOrderPage() {
    ob_start();

    // base URL
    $wo = new WarungOptions();
    $adminURL = $wo->getAdminPageURL();
    $orderURL = add_query_arg("adm_page","order",$adminURL);
    $baseURL = $orderURL;
    
    // filters
    
    // 1. search
    $orderSearch = $_REQUEST["order_search"];
    if (!empty($orderSearch)) {
        // add search state for paging
        $baseURL = add_query_arg("order_search", $orderSearch, $baseURL);
    }
    
    // 2. status: new, paid, process, sent 
    $orderStatus = $_REQUEST["order_status"];
    if (empty($orderStatus)) {
        $orderStatus = "1"; // default set to new
        // add orderstatus state for paging
        $baseURL = add_query_arg("order_status", $orderStatus, $baseURL);
    } else {
        // add orderstatus state for paging
        $baseURL = add_query_arg("order_status", $orderStatus, $baseURL);
    }
    
    $urlpart = parse_url($baseURL);
    $backURL = $_SERVER["REQUEST_URI"];//$urlpart["path"] . "?" . $urlpart["query"];  
    
    // view: tab states
    $activeTab = array ("1"=>"","2"=>"","3"=>"","4"=>"");
    $activeTab[$orderStatus] = 'class="active"';
    
    // show notification entries only?
    $showNotificationEntries = $_REQUEST["order_notification"];
    if (!empty($showNotificationEntries)) {
        $showNotificationEntries = true;
    } else {
        $showNotificationEntries = false;
    }
    
    // paging
    $curPage = $_REQUEST["op"];
    if (empty($curPage)) {
        $curPage = 1;
    }
    
    // show search bar
    ?>
    
    <form class="well form-search" action="<?=$orderURL?>" method="POST">
        <div class="input-append">
        <input type="text" placeholder="search name, city" class="input-medium search-query" name="order_search" value="<?=$orderSearch?>"><button class="btn" type="submit">Go!</button>
        </div>
    </form>
    <?php
    
    // get notification
    $os = new OrderService();
    $notifications = $os->getStatusNotification();
    $statuses = $os->getAllStatus();
    
    if (!empty($notifications)):
        foreach($notifications as $row) {
    ?>
    <ul class="nav nav-tabs">
        <li <?=$activeTab["1"]?>><a href="<?=add_query_arg(array("order_status"=>"1"),$orderURL)?>">New <? if ($row->new > 0) :?><span class="badge badge-warning"><?=$row->new?></span><? endif; ?></a></li>
        <li <?=$activeTab["2"]?>><a href="<?=add_query_arg(array("order_status"=>"2"),$orderURL)?>">Paid <? if ($row->paid > 0) :?><span class="badge badge-warning"><?=$row->paid?></span><? endif;?></a></li>
        <li <?=$activeTab["3"]?>><a href="<?=add_query_arg(array("order_status"=>"3"),$orderURL)?>">Process <? if ($row->process > 0) :?><span class="badge badge-warning"><?=$row->process?></span><? endif;?></a></li>
        <li <?=$activeTab["4"]?>><a href="<?=add_query_arg(array("order_status"=>"4"),$orderURL)?>">Sent <? if ($row->sent > 0) :?><span class="badge badge-warning"><?=$row->sent?></span><? endif;?></a></li>
    </ul>
    <?
        }
    endif;
    
    // get the order entry
    $orders = array();
    if (!empty($orderSearch)){
        // do search query
        $orderSearch = trim($orderSearch);
        $orderSearch = '%'.str_ireplace(' ', '%', $orderSearch).'%';
        $orders = $os->searchOrder($orderSearch, $curPage);
    } else {
        $orders = $os->getOrderByStatus($orderStatus, $showNotificationEntries, $curPage);
    }
    
    $orderRows = $orders["data"];
    
    $totalPage = $orders["totalPage"];

    if (!empty($orderRows)) :?>
    <?php if(!empty($orderSearch)):?>
    <div class="alert"><a href="<?=$orderURL?>" class="close">×</a>Search Results </div>
    <?php endif; ?>
    <table class="table">
        <thead>
            <tr>
                <th>Create Date</th>
                <th>Info</th>
                <th>City</th>
                <?php if($orderStatus == OrderService::$STATUS_SENT) :?> 
                <th>Delivery Number</th>
                <?php endif; ?>
                <?php if(!empty($orderSearch)) :?> 
                <th>Status</th>
                <?php endif; ?>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderRows as $order) { 
                $shippingInfo = $order->shippingInfo;
                $editURL = add_query_arg(array("order_id"=>$order->id,"adm_page"=>"order_update","back"=>urlencode($backURL)), $adminURL);
                $logURL = add_query_arg(array("order_id"=>$order->id,"adm_page"=>"order_status_log"), $adminURL);
                $sendMailURL = add_query_arg(array(
                    "order_id"=>$order->id,
                    "adm_page"=>"order_send_mail",
                    "template"=>"order_new",
                    "back"=>urlencode($backURL)), $adminURL);
                $sendMailSentURL = add_query_arg(array(
                    "order_id"=>$order->id,
                    "adm_page"=>"order_send_mail",
                    "template"=>"order_sent",
                    "back"=>urlencode($backURL)), $adminURL);
            ?>
            <tr class="show-info">
                <td><?=($order->requireAttention?'<span class="label label-warning">Check</span> ':'')?>
                    <p class="info-less"><?=WarungUtils::relativeTime($order->dtcreated)?></p>
                    <div class="info hide">
                        <dl>
                            <dt>Create Date</dt><dd><?=strftime('%d/%b/%y %H:%M',strtotime($order->dtcreated));?></dd>
                            <dt>Status Date</dt><dd><?=strftime('%d/%b/%y %H:%M',strtotime($order->dtstatus));?></dd>
                        </dl>
                    </div>
                </td>
                <td>
                    <p class="info-less"><?=$shippingInfo->name?></p>
                    <dl class="info hide">
                        <dt>Name</dt><dd><?=$shippingInfo->name?></dd>
                        <dt>Phone</dt><dd><?=$shippingInfo->phone?></dd>
                        <dt>Items</dt><dd><?=WarungUtils::formatItems($order->items,"unstyled")?></dd>
                        <dt>Price</dt><dd><?=WarungUtils::formatCurrency($order->totalPrice)?></dd>
                        <dt>Shipping Price</dt><dd><?=WarungUtils::formatCurrency($order->shippingPrice)?></dd>
                        <dt>Total Price</dt><dd><?=WarungUtils::formatCurrency($order->totalPrice + $order->shippingPrice)?></dd>
                        <dt>Shipping Services</dt><dd><?=$order->shippingServices?></dd>
                        <dt>Payment Method</dt><dd><?=$order->paymentMethod?></dd>
                    </dl>
                </td>
                <td>
                    <p class="info-less"><?=ucwords($shippingInfo->city)?></p>
                    <div class="info hide">
                        <dl>
                            <dt>Alamat</dt>
                            <dd>
                                <address>
                                <?=str_replace("\n", "<br/>", $shippingInfo->address)?>
                                <br/><?=ucwords($shippingInfo->city)?> <?=ucwords($shippingInfo->postalCode)?>
                                </address>
                            </dd>
                        </dl>
                    </div>
                </td>
                <?php if($orderStatus == OrderService::$STATUS_SENT) {?> 
                <td>
                    <p class="info-less"><?=$order->deliveryNumber?></p>
                    <div class="info hide">
                    <dl><dt>Delivery Number</dt><dd><?=$order->deliveryNumber?></dd></dl>
                    </div>
                </td>
                <?php } ?>
                
                <?php if(!empty($orderSearch)) :?> 
                <td><?=$order->statusName?></td>
                <?php endif;?>
                <td>
                    <a href="<?=$editURL?>" title="Edit Status" class="btn btn-small">Status</a>
		    <a href="<?=$logURL?>" title="Log Status" class="btn btn-small">Log</a>
                    <?php if($order->statusId == 1): ?>
                    <a href="<?=$sendMailURL?>" title="Send Mail" class="btn btn-small">Email</a>
                    <?php endif;?>
                    <?php if($order->statusId == 4): ?>
                    <a href="<?=$sendMailSentURL?>" title="Send Mail" class="btn btn-small">Email</a>
                    <?php endif;?>
                </td>
            </tr>
            <? } ?>
        </tbody>
    </table>       
    <!-- paging -->
    
    <div class="pagination">
    <ul>
        <li <?=($curPage==1?'class="disabled"':'')?>>
            <a href="<?=($curPage==1?'':add_query_arg(array("op"=>max(array(1,$curPage-1))),$baseURL))?>">Prev</a>
        </li>
    <?php for ($j = 1; $j <= $totalPage; $j++) { ?>
        <li <?=($j==$curPage?'class="active"':'')?>><a href="<?=add_query_arg(array("op"=>$j),$baseURL)?>"><?=$j?></a></li>    
    <?php } ?>
        <li <?=($curPage==$totalPage?'class="disabled"':'')?>><a href="<?=$curPage==$totalPage?'':add_query_arg(array("op"=>min(array($totalPage, $curPage+1))),$baseURL)?>">Next</a></li>
    </ul>
    </div>
    
    <script type="text/javascript">                                         
        $(document).ready(function() {
            $("tr.show-info").click(function(e) {
                
                // prevent event bubbling
                if ($(e.target).is("td") || $(e.target).is("dd") || 
                    $(e.target).is("dt") || $(e.target).is("address")|| 
                    $(e.target).is("li") || $(e.target).is("p")) {
                    
                    $(this).find(".info").each(function(i,val){
                        $(val).toggle();
                    });
                    
                    $(this).find(".info-less").each(function(i,val){
                        $(val).toggle();
                    });
                }
                
            });
            
        });
    </script> 
    
    <? endif; 
    
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showAdminOrderUpdatePage() {
    ob_start();
    
    $orderId = $_REQUEST["order_id"];
    
    $os = new OrderService();
    $wo = new WarungOptions();
    $baseURL = $wo->getAdminPageURL();
    $orderURL = add_query_arg("adm_page","order",$baseURL);
    $orderUpdateURL = add_query_arg(array("adm_page"=>"order_update","order_id"=>$orderId),$baseURL);
    
    
    $backURL = $_REQUEST["back"];
    if (empty($backURL)) {
        $backURL = $orderURL;
    }

    $order = $os->getOrderById($orderId);
    
    $result = "";
    $error = "";
    
    if (isset($order->id)) {
        
        $shippingInfo = $order->shippingInfo;
        $newStatusId = $_REQUEST["status_id"];
        $deliveryNumber = $_REQUEST["delivery_number"];
        
        if (!empty($newStatusId)) {
            // do update
            $os->updateStatus($orderId, $newStatusId);
            $result = "Changes Saved";
        }
        
        if (!empty($deliveryNumber)) {
            $os->updateDeliveryNumber($orderId, $deliveryNumber);
            $result="Changes Saved";
        
            
            $names = explode(" ",$shippingInfo->name);
            if (!empty($names)) {
                $names = ucwords(strtolower($names[0]));
            } else {
                $names = '';
            }
            // send sms here
            $sms = wrg_sendsms(
                    $shippingInfo->phone, 
                    wrg_orderSentSMSTemplate(array(
                        "shipping.name"=> $names,
                        "order.delivery_number"=>$deliveryNumber)
                    )
            );
            
        }
        
        if (empty($result)) {
        
            $statuses = $os->getAllStatus();
        
    ?>
    <form class="well" method="POST" action="<?=$orderUpdateURL?>">
        <input type="hidden" name="order_id" value="<?=$order->id?>">
        <input type="hidden" name="back" value="<?=$backURL?>">
        <label for="status_id">Status</label>
        <select name="status_id" id="status_id">
            <?php foreach($statuses as $status_id=>$description) { ?>
            <option value="<?=$status_id?>" <?=$status_id==$order->statusId?"selected":''?>><?=$description?></option>
            <?php } ?>
        </select>
        <label for="delivery_number">Airway Bill</label>
        <input type="text" name="delivery_number" value="<?=(!empty($order->deliveryNumber)?$order->deliveryNumber:'')?>"></input>
        <label></label>
        <a href="<?=$backURL?>" class="btn">Back to Order</a>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
    
    
    <?php
        }
    } else {
        $error = "Invalid order Id";
    }
    
    if ($error) {
        $backURL = $orderUpdateURL;
    }
    
    if (!empty($error)) {
        echo '<div class="alert alert-error"><a class="close" href="'.$backURL.'">×</a>'.$error.'</div>';
    } else if (!empty($result)) {
        // redirect to backURL
        header('Location: '.$backURL);
        return;
//        echo '<div class="alert alert-success"><a class="close" href="'.$backURL.'">×</a>'.$result.'</div>';
    }
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_orderNewMailTemplate($args=array()) {
    ob_start();
    $ret = "";
    
    $td_style = 'style="vertical-align: top;
            border-bottom: 1px solid rgb(238,238,238); 
            text-align: left;
            padding-left:10px;"';
    
    $table_style = 'style="border-spacing: 0px;"';
    
    $td_title_style = 'style="vertical-align: top;
            border-bottom: 1px solid rgb(238,238,238); 
            text-align: left;
            padding-left:10px;
            text-align: right;
            font-weight: bold;
            padding: 2px;"';
    
    $ul_style='style="padding-left: 20px;"';
    
    ?>
    <style>
        table td { 
            vertical-align: top;
            border-bottom: 1px solid rgb(238,238,238); 
            text-align: left;
            padding-left:10px;
        }

        table {
            border-spacing: 0px;
        }

        .title {
            text-align: right;
            font-weight: bold;
            padding: 2px;
        }

        table td ul {
            padding-left: 20px;
        }
    </style>
         <div>
             <p>Mba Sofie,</p>

         <table <?php echo $table_style?>>
             <tr>
                 <td class="title" <?php echo $td_title_style?>>Nama</td>
                <td <?php echo $td_style?>>%shipping.name%</td>
             </tr>
             <tr>
                 <td class="title" <?php echo $td_title_style?>>Alamat</td>
                 <td <?php echo $td_style?>>%shipping.address%<br/>%shipping.city%</td>
             </tr>
             <tr>
                 <td class="title" <?php echo $td_title_style?>>Telepon</td>
                 <td <?php echo $td_style?>>%shipping.phone%</td>
             </tr>
             <tr>
                 <td class="title" <?php echo $td_title_style?>>Pesanan</td>
                 <td <?php echo $td_style?>>%order.items%</td>
             </tr>
             <tr>
                 <td class="title">Catatan</td>
                 <td <?php echo $td_style?>>%shipping.additionalInfo%</td>
             </tr>
         </table>
         
             <p>
         Thx,<br/>
         Reni
             </p>
         </div>
    
    <?php
    
    $ret = ob_get_contents();
    ob_end_clean();
    
    if (!empty($args)) {
        $ret = WarungUtils::generateTemplate($ret, $args);
    }
    
    return $ret;
}

function wrg_orderSentMailTemplate($args=array()) {
    ob_start();
    $ret = "";
    
    ?>
         <div>
            <p>Bpk/Ibu %shipping.name%,</p>

            <p>Pesanan sudah kami kirim dengan nomor resi berikut <b>%order.delivery_number%</b>
         
            <p>Untuk tracing/tracking paket bisa dicek di web berikut:</p>    
            <ul>
                <li><a href="http://jne.co.id/">JNE</a></li>
                <li><a href="http://tiki-online.com/tracking/track_single">TIKI</a></li>
                <li><a href="http://www.pandulogistics.com/etools.asp?submenu=Tracing">Pandusiwi</a></li>
                <li><a href="http://www.posindonesia.co.id/">Pos Indonesia</a></li>
            </ul>
                
            <p>
                Thx,<br/>
                Reni
            </p>
         </div>
    
    <?php
    
    $ret = ob_get_contents();
    ob_end_clean();
    
    if (!empty($args)) {
        $ret = WarungUtils::generateTemplate($ret, $args);
    }
    
    return $ret;
}

function wrg_orderSentSMSTemplate($args=array()) {
    $ret = "Halo %shipping.name%, pesanan sudah kami kirim. Ini nmr resinya:\n%order.delivery_number%\nTrmksh byk\n\n-Reni\nWarungsprei.com";
    
    if (!empty($args)) {
        $ret = WarungUtils::generateTemplate($ret, $args);
    }
    
    return $ret;
}

function wrg_showAdminOrderSendMailPage() {
    ob_start();
    
    // param
    $orderId = $_REQUEST["order_id"];
    $doSendMail = $_REQUEST["do_sendmail"];
    $template = $_REQUEST["template"];
    if (empty($template)) {
        $template = "order_new";
    }
    
    $wo = new WarungOptions();
    $adminURL = $wo->getAdminPageURL();
    $sendMailURL = add_query_arg(array("order_id"=>$orderId,"adm_page"=>"order_send_mail"), $adminURL);
    $orderURL = add_query_arg("adm_page","order",$baseURL);
    $backURL = $_REQUEST["back"];
    if (empty($backURL)) {
        $backURL = $orderURL;
    } else {
        $sendMailURL = add_query_arg(array("back"=>urlencode($backURL)), $sendMailURL);
    }
    
    $os = new OrderService();
    $order = $os->getOrderById($orderId);
    
    $result = "";
    $error = "";
    
    if (isset($order->id)) {
        
        if (!empty($doSendMail)) {
            // do send mail    
            $mailTo = $_REQUEST["mail_to"];
            $subject = $_REQUEST["mail_subject"];
            $message = $_REQUEST["mail_message"];
            
            
            if (!empty($mailTo) && !empty($subject) && !empty($message)) {
                
                $headers = "Content-type: text/html;\r\n";
                $headers .= "From: Warungsprei.com <info@warungsprei.com>\r\n";
                
                if (wp_mail($mailTo, $subject, $message, $headers)) {
                    $result = "Email order sudah terkirim";
                } else {
                    $error = "Gagal mengirim Email order";
                }
            } else {
                $error = "Please fill required fields";
            }
        } else {
            // show send mailform
            $mailTemplate = "";
            
        
            if ($template == "order_new") {
                // default use new order template
                $shippingInfo = $order->shippingInfo;
                
                $mailTemplate = wrg_orderNewMailTemplate(array(
                    "shipping.name"=>$shippingInfo->name,
                    "shipping.phone"=>$shippingInfo->phone,
                    "shipping.address"=>nl2br($shippingInfo->address),
                    "shipping.city"=>ucwords($shippingInfo->city),
                    "shipping.additionalInfo"=>$shippingInfo->additionalInfo,
                    "order.items"=>WarungUtils::formatItems($order->items)
                ));
                
                $mail_to = 'butikceria@gmail.com';
                $mail_subject = ucwords($shippingInfo->name.' '.$shippingInfo->city);
            } else if ($template == "order_sent") {
                $shippingInfo = $order->shippingInfo;
                $mailTemplate = wrg_orderSentMailTemplate(array(
                    "shipping.name"=>ucwords($shippingInfo->name),
                    "order.delivery_number"=>$order->deliveryNumber
                ));
                
                $mail_to = $shippingInfo->email;
                $mail_subject = "[Warungsprei.com] Pengiriman Pesanan #".$order->id;
            }
            
            ?>
    <form id="send_mail_form" class="well" method="POST" action="<?=$sendMailURL?>">
        <input type="hidden" name="order_id" value="<?=$order->id?>">
        <input type="hidden" name="template" value="<?=$template?>">
        <input type="hidden" name="back" value="<?=$backURL?>">
        
        <label for="mail_to">To</label>
        <input type="text" name="mail_to" value="<?=$mail_to?>"/>
        
        <label for="mail_subject">Subject</label>
        <input type="text" name="mail_subject" value="<?=$mail_subject?>"/>
        
        <label for="mail_message">Message</label>
        <textarea id="mail_message" name="mail_message" rows="10" class="span4"><?=$mailTemplate?></textarea>

        <label></label>
        <a href="<?=$backURL?>" class="btn back">Back to Order</a>
        <input type="submit" class="btn btn-primary" name="do_sendmail" value="Send Mail"/>
        
    </form>
    <script type="text/javascript">
        $(document).ready(function(){
            $("form#send_mail_form").validate({
                rules: {
                            mail_to: {// compound rule
                                required: true,
                                email: true
                            },
                            mail_subject: {
                                required: true
                            },
                            mail_message: "required"
                        }
            });
            
            $('a.back').click(function(){
		parent.history.back();
		return false;
            });
            
            $("#mail_message").redactor({mobile:true});
        });
    </script>
            <?php
        }
        
    } else {
        $error = 'Invalid order id';
    }

//    if (!empty($error)) {
//        $backURL = $sendMailURL;
//    }
    
    if (!empty($error)) {
        echo '<div class="alert alert-error"><a class="close" href="'.$backURL.'">×</a>'.$error.'</div>';
    } else if (!empty($result)) {
        header('Location: '. $backURL);
        return;
        //echo '<div class="alert alert-success"><a class="close" href="'.$backURL.'">×</a>'.$result.'</div>';
    }
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showAdminOrderLogPage() {
    ob_start();
    
    $orderId = $_REQUEST["order_id"];
    
    $os = new OrderService();
    $wo = new WarungOptions();
    $baseURL = $wo->getAdminPageURL();

    $logs = $os->getStatusHistory($orderId);
    
    $updateStatus = "";
    
    if (!empty($logs)) {
    ?>
    <h3>Order Log</h3>
        <ol>
            <?php foreach ($logs as $log) { ?>
            <li>
                <?=$log->dtlog?> <?=$log->status?>
            </li>
            <? } ?>
        </ol>
    
    <?php
    }
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showAdminOrderCopyPage() {
    ob_start();
    
    // param
    $orderId = $_REQUEST["order_id"];
    
    // response
    $result = "";
    $error = "";
    
    // mandatory vars
    $os = new OrderService();
    $wo = new WarungOptions();
    $baseURL = $wo->getAdminPageURL();
    $orderURL = add_query_arg("adm_page","order",$baseURL);

    // do copy
    if ($orderId) {
        
        $newOrderId = $os->copyOrder($orderId);

        if ($newOrderId) {
            // copy success
            $result = "Succes copying order with Id: ".$orderId. " to new order with Id: " . $newOrderId;
        } else {
            // copy failed
            $error = "Failed to copy order with id: ".$orderId;
        }
    } else {
        // invalid order id
        
        $error = "Invalid or missing order_id";
    }
    
    if (!empty($error)) {
        echo '<div class="alert alert-error"><a class="close" href="'.$orderURL.'">×</a>'.$error.'</div>';
    } else if (!empty($result)) {
        echo '<div class="alert alert-success"><a class="close" href="'.$orderURL.'">×</a>'.$result.'</div>';
    }
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showAdminOrderAddPage() {
    ob_start();
    
    $wo = new WarungOptions();
    
    // url
    $coURL = $wo->getCheckoutURL();
    $baseURL = $wo->getAdminPageURL();
    $orderURL = add_query_arg("adm_page","order",$baseURL);
    $saveURL = add_query_arg(array("wrg_action"=>"confirm"), $coURL);
    $createOrderURL = add_query_arg(array("wrg_action"=>"pay"), $coURL);

    
    $addStatus = "";
    
    // get kasir
    $kasir = new WarungKasir2();

    // get user info
    $userInfo = $kasir->getSavedUserInfo();
    
    ?>
    <?php if(!empty($addStatus)):?>
    <div class="alert"><?=$addStatus?></div>
    <?php endif; ?>
    
    <!-- cart -->
    <h3>1. Fill Shopping Cart</h3>
    <div class="well">
        <input type="text" id="searchItem" placeholder="Cari item">
        <div id="cart"><?=wrg_htmlGetCart()?></div>
    </div>
    
    <!-- shipping form -->
    <h3>2. Fill Shipping Form</h2>
    <form method="POST" name="wCart_shipping_form" id="wCart_shipping_form2" action="<?=$saveURL?>" class="well">
        <? wp_nonce_field('warung_shipping_form', 'warung_shipping_form_nonce'); ?>
        <label for="semail">Email *</label>
        <input type="text" name="semail" value="<?= $userInfo->email ?>" maxlength="60">

        <label for="sphone">HP (handphone) *</label>
        <input type="text" name="sphone" value="<?= $userInfo->phone ?>" maxlength="31">
        
        <label for="sname">Nama Penerima *</label>
        <input type="text" name="sname" value="<?= $userInfo->name ?>" maxlength="60">
        
        <label for="saddress">Alamat *</label>
        <textarea name="saddress"><?= $userInfo->address ?></textarea>
        
        <label for="scity">Kota *</label>
        <input type="text" id="scity" name="scity" value="<?= $userInfo->city?>">                               

        <label for="spostalcode">Kode Pos *</label>
        <input type="text" name="spostalcode" value="<?= $userInfo->postalCode ?>" maxlength="60">
        <a href="http://kodepos.posindonesia.co.id/" target="_blank">&nbsp;kode pos</a>

        <label for="spaymentmethod">Pembayaran *</label>
        <?= WarungUtils::htmlSelect('spaymentmethod', 'spaymentmethod', array(""=>"-- pilih metode pembayaran --","bca"=>"Transfer ke BCA","mandiri"=>"Transfer ke Mandiri"), $userInfo->paymentMethod) ?>                                  

        <label for="sadditional_info">Info Tambahan</label>
        <textarea name="sadditional_info"><?= $userInfo->additionalInfo ?></textarea>

        <input type="hidden" name="scountry" value=" *">
        <input type="hidden" name="step" value="2">

        <div class="form-actions">
            <input type="submit" name="scheckout" class="btn" value="Calculate Ongkir">
            <a href="<?=$createOrderURL?>" class="btn btn-primary create-order">Create Order</a>
        </div>

    </form>
    
    <script type="text/javascript">                                         
        $(document).ready(function() {
            
            // product suggest
            var theUrl = "<?=add_query_arg(array("adm_page"=>"product_search","json"=>"1"), $baseURL);?>";
            var cartUrl = "<?=add_query_arg(array("adm_page"=>"cart_show","json"=>"1"), $baseURL);?>";
            // add to cart
            var addUrl = "<?=get_option("home")?>/";
            
            $("input#searchItem").jsonSuggest({
                url: theUrl, 
                onSelect: function(data) {
                    
                    $("body").css("cursor", "progress");
                    
                    if (typeof data.product_option === "undefined") {
                        data.product_option = -1;
                    }
                    
                    // add to cart
                    $.post(
                        addUrl, 
                        { wcart_ordernow: "1", product_id: data.id, product_option: data.product_option }, 
                        function(res) {
                            // dont use data, its just html response

                            // get cart
                            $.get(cartUrl, function(data2) {
                                $("#cart").html(data2);
                                
                                $("body").css("cursor", "auto");
                            });
                        });
                        
                     // clear value
                     $("input#searchItem").val("");
                   
                }});
                
            // city suggest
            var theCityUrl = "<?=add_query_arg(array("adm_page"=>"city_search","json"=>"1"), $baseURL);?>";
            $("input#scity").jsonSuggest({url: theCityUrl, onSelect: function(data){
                $("form#wCart_shipping_form2").submit();
            }}); 
                
                
            // add order form
            $("form#wCart_shipping_form2").ajaxForm({
                beforeSubmit: function(e){
                    
                    var val = $("form#wCart_shipping_form2").validate({
                        rules: {
                            semail: {// compound rule
                                required: true,
                                email: true
                            },
                            sphone: {
                                required: true,
                                number: true
                            },
                            scity: "required",
                            sname: "required",
                            saddress: "required",
                            spaymentmethod: "required",
                            spostalcode: "required"
                        }
                        /*
                        ,messages: {
                        semail: "Tolong isi field ini."
                        }
                        */

                    }).form();
                    
                    if (val == true) {
                        $("body").css("cursor", "progress");
                    }
                },
                success: function(e){
                    // load cart
                    $.get(cartUrl, function(data2) {
                        $("#cart").html(data2);
                        $("body").css("cursor", "auto");
                    });
                    
                }
            });
            
            // create order
            $(".create-order").click(function(e){
                e.preventDefault();
                
                $("body").css("cursor", "progress");
                
                var theURL = $(this).attr("href");
                
                $.get(theURL, function(data){
                    window.location.href = "<?=$orderURL?>";
                });
            });
        });
    </script> 
    
    
    <?php
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

function wrg_showAdminOrderAddFromTextPage() {
    ob_start();
    
    // init
    $wo = new WarungOptions();
    $o = new OrderService();
    $orders = $o->getTempOrders();
    
    // url
    $baseURL = $wo->getAdminPageURL();
    $orderURL = add_query_arg("adm_page","order",$baseURL);
    $addTempOrderURL = add_query_arg("adm_page","order_add_from_text",$baseURL);
    
    // status
    $actionStatus = "";
    $error = "";
    $backURL = $addTempOrderURL;
    
    // action
    if ($_REQUEST["order_text"]) {
        $ret = $o->putTempOrder($_REQUEST["order_text"]);
        
        if ($ret) {
            $actionStatus = "Succes adding temp order";
        } else {
            $error = "Failed adding temp order";
        }
                
    } else if ($_REQUEST["order_id"]) {
        $ret = $o->deleteTempOrder($_REQUEST["order_id"]);
        
        if ($ret) {
            $actionStatus = "Success delete temp order with id ". $_REQUEST["order_id"];
        } else {
            $error = "Failed delete temp order with id: ". $_REQUEST["order_id"];
        }
        
        $backURL = $addTempOrderURL;
    }
    
    ?>
    <?php if(!empty($error)):?>
    <div class="alert alert-error"><a href="<?=$backURL?>" class="close">x</a><?=$error?></div>
    <?php elseif(!empty($actionStatus)): ?>
    <div class="alert alert-success"><a href="<?=$backURL?>" class="close">x</a><?=$actionStatus?></div>
    <?php else: ?>
    <h3>Add Order from Text</h3>
    <form id="add_temp_order_form" class="well" method="POST" action="<?=$addTempOrderURL?>">
        <label for="order_text">Text</label>
        <textarea id="order_text" name="order_text" rows="10" class="span4"></textarea>

        <label></label>

        <div class="form-actions">
            <a href="<?=$orderURL?>" class="btn back">Back to Order</a>
            <input type="submit" class="btn btn-primary" name="action" value="Save Order"/>
        </div>
        
    </form>
    
    <!-- list unparsed order -->
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Text</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order) { 
                $editURL = add_query_arg(array("order_id"=>$order->id), $addTempOrderURL);
            ?>
            <tr class="show-info">
                <td>
                    <p class="info-less"><?=WarungUtils::relativeTime($order->dtcreated)?></p>
                </td>
                <td>
                    <p class="info-less"><?=$order->text?></p>
                </td>
                <td>
                    <a href="<?=$editURL?>" title="Delete Order" class="btn btn-small">Delete</a>
                </td>
            </tr>
            <? } ?>
            <?php endif;?>
        </tbody>
    </table>       
    
    <?php endif;
    
    $ret = ob_get_contents();
    ob_end_clean();

    return $ret;
}

// ================== JSON =====================================

function wrg_jsonProducts() {
    ob_start();
    
    // get query
    $query = $_REQUEST["search"];
    
    // get product
    WarungProduct::search($query);
    
    $ret = ob_get_contents();
    ob_end_clean();
    
    return $ret;
}

function wrg_jsonCity() {
    ob_start();
    
    // get query
    $query = $_REQUEST["search"];
    
    // get cities
    $res = array();
    $cities = GenericShippingService::searchAllDestinations($query);
    foreach($cities as $key=>$cityName) {
        $res[] = (object)array("id"=>$key,"text"=>$key);
    }
    
    echo json_encode($res);
    
    $ret = ob_get_contents();
    ob_end_clean();
    
    return $ret;
}

function wrg_htmlGetCart() {
    $cart = new WarungCart2();
    $kasir = new WarungKasir2();
    $wo = new WarungOptions();
    
    // url
    $coURL = $wo->getCheckoutURL();
    $baseURL = $wo->getAdminPageURL();
    $updateQttURL = add_query_arg(array("wrg_action" => "updateCart"), $coURL);
    
    // cart 
    $cartEntry = $cart->getItems();
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
    
    $cartSum = $kasir->getSummary($cartEntry, $userInfo, $destination);
    
    if (!empty($cartEntry)): ?>
    <form method="POST" action="<?= $updateQttURL ?>" id="cart-form">
        <? wp_nonce_field('warung_detailed_cart', 'warung_detailed_cart_nonce'); ?>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartEntry as $i) : 
                $productInfo = $i->attachment["product"];
                $removePage = add_query_arg(array("wrg_action" => "removeCartItem", "ci" => $i->cartId), $coURL);
            ?>
            <tr>
            <td>
                <div class="thumbnail span2">
                    <img src="<?=$productInfo["thumbnail"]?>" alt="">
                    <p><?=$i->name?></p>
                </div>
            </td>
            <td>
                <input type="text" class="update-quantity input-mini" name="qty_<?=$i->cartId?>" value="<?=$i->quantity?>" size="1">
            </td>
            <td>
                <?=WarungUtils::formatCurrency($i->price)?>
            </td>
            <td><a href="<?=$removePage?>" class="remove-cart-item"><i class="icon-remove"></id></a></td>
            </tr>
            <?php endforeach;?>
            <tr>
                <td colspan="100%">
                    <div class="pull-right">
                    <a href="<?=add_query_arg("wrg_action","clearCart", $wo->getHomeURL())?>" class="btn clear-cart">Clear Cart</a>
                    <input type="submit" name="wc_update" class="btn" value="Update" title="Klik tombol ini jika ingin mengupdate jumlah barang"/>
                    </div>
                </td>
            </tr>
            
            <!-- summary -->
            <tr><td colspan="3" class="wcart-td-footer">Total Sebelum Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["totalPrice"]) ?></span></td></tr>
            <?php if (isset($cartSum["shipping"])) : ?>
            <tr>
                <td colspan="3" class="wcart-td-footer">Ongkos Kirim (<?= WarungUtils::formatWeight($cartSum["totalWeight"]) ?>)<?php echo !empty($cartSum["shipping.name"]) ? " - ".$cartSum["shipping.name"]: ''; ?></td>
                <td class=""><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["shipping"]) ?></span></td></tr>
            <?php endif; ?>
            <?php if(!empty($cartSum["discount"])):?>
            <tr>
                <td colspan="3" class="wcart-td-footer">Diskon</td>
                <td class="wcart-td-footer"><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["discount"]) ?></span></td>
            </tr>
            <?php endif; ?>
            <tr><td colspan="3" class="wcart-td-footer">Total Setelah Ongkos Kirim</td><td class="wcart-td-footer"><span class="wcart_total"><?= WarungUtils::formatCurrency($cartSum["totalPrice.grand"]) ?></span></td></tr>
            <?php if(isset($cartSum["shipping.error"])):foreach($cartSum["shipping.error"] as $err){?>
            <tr>
                <td colspan="3" class="wcart-td-footer">Catatan</td>
                <td class="wcart-td-footer"><span class="wcart_total"><?= $err ?></span></td>
            </tr>
            <?php }
            endif; 
            ?>
        </tbody>
    </table>
    </form>
    <script type="text/javascript">
        $(document).ready(function() {
            // cart 
            
            var cartUrl = "<?=add_query_arg(array("adm_page"=>"cart_show","json"=>"1"), $baseURL);?>";
            
            // remove item
            $("a.remove-cart-item").click(function(e){
                e.preventDefault();

                // cursor busy
                $("body").css("cursor", "progress");

                var removeURL = $(this).attr("href");
                
                $.get(removeURL, function(e) {
                    // load cart
                    $.get(cartUrl, function(data2) {
                        $("#cart").html(data2);
                        
                        // cursor normal
                        $("body").css("cursor", "auto");
                    });
                    
                });
                
                return false;
            });
            
            // update quantity
            $("#cart-form").ajaxForm(function(e){
                
                // cursor busy
                $("body").css("cursor", "progress");
                
                // load cart
                $.get(cartUrl, function(data2) {
                    $("#cart").html(data2);
                    
                    // cursor normal
                    $("body").css("cursor", "auto");
                });
                
            });
            
            // clear cart
            $("a.clear-cart").click(function(e) {
                e.preventDefault();

                // cursor busy
                $("body").css("cursor", "progress");
                
                var url = $(this).attr("href");
                
                $.get(url, function(e) {
                    // load cart
                    $.get(cartUrl, function(data2) {
                        $("#cart").html(data2);
                        
                        // cursor normal
                        $("body").css("cursor", "auto");
                    });
                    
                });
                
                return false;
            });
            
        });
    </script>
    <?php endif;
}

function format_msisdn($msisdn) {
    if (is_numeric($msisdn)) {
        return preg_replace('/^((0)|(\+0)|(62))/', '+62', $msisdn);
    } else {
       return $msisdn;
    }
}

function wrg_sendsms($msisdn, $text) {
    if(is_numeric($msisdn)) {
        
        $msisdn = urlencode(format_msisdn($msisdn));
        $text = urlencode($text);
        $send_url = "https://sent.ly/command/sendsms?username=sihendra@gmail.com&password=c0b4c0b4&to=$msisdn&text=$text";
        
	$log = 'about to hit: '.$send_url;
//        var_dump($log);

        $response = @wp_remote_fopen($send_url);
        
        //error_log('sms response: '.$response);
        
        if (strpos(strtolower($response),'error') === FALSE) {
            // not error
            return TRUE;            
        }
    } else {
        //error_log('msisdn tdk valid: '.json_encode($msisdn));
    }
    
    return FALSE;
}
?>
