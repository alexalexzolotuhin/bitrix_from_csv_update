<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("sale");
CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");
//




class OrdersStatus{
    public function __construct()
    {
        global $DB;
    $arFilter = Array(
        "@STATUS_ID" => array("B", "G", "H", "I","M","N","P","S"),
        ">=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), mktime(0, 0, 0, date("n"), 1, date("Y")))
        );
    $rsSales = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter);
    $count=0;

    $mass_for_send=array();
    while ($arSales = $rsSales->Fetch())
    {
        $count++;
        if($count==200) {
            break;
        }
       //получили id заказа
        $ID_ORDER=$arSales['ID'];
        $mass_tovarov=$this->getProductsWithEmptyWeghtByIdOrder($ID_ORDER);
        if(!empty($mass_tovarov)){
          /*  echo "<pre>";
            debug($arSales['ID']);
            debug($mass_tovarov);
            echo "</pre>"; */
            foreach ($mass_tovarov as $val ){
                $val['ORDER_ID']=$ID_ORDER;
                $mass_for_send[]= $val;
            }
        }
    }
      // debug($mass_for_send);

       $html=' <h1>Заказы с пустым весом в товаре</h1>';
       $html.=' <table>';
       $html.='<tr><td>ORDER_ID</td><td>Товар</td></tr>';
        foreach ($mass_for_send as $value) {
            $html.='<tr><td><a href="'.$_SERVER['SERVER_NAME'].'/bitrix/admin/sale_order_detail.php?ID='.$value['ORDER_ID'].'&filter=Y&set_filter=Y&lang=ru">'.$value['ORDER_ID'].'</a></td><td><a href="'.$_SERVER['SERVER_NAME'].$value['DETAIL_PAGE_URL'].'">'.$value['NAME'].'</a></td></tr>';
       }
        $html.= '</table>';

    $arEventFields = array(
    "HTML" => $html,
    );
    CEvent::Send("ORDER_STATUS_WEIGHT", 's1', $arEventFields);

    }

    //получаем свойства заказа по id c пустым весом)
    function getProductsWithEmptyWeghtByIdOrder($ORDER_ID){
        $dbBasketItems = CSaleBasket::GetList(array(), array("ORDER_ID" => $ORDER_ID), false, false, array());
        while ($arItems = $dbBasketItems->Fetch()) {
           //  debug($arItems);
            //вес товара по id
            //проверяем на пустой вес товар
            $ar_res = CCatalogProduct::GetByID($arItems['PRODUCT_ID']);
            if(empty($ar_res['WEIGHT'])){
                $ret[] = $arItems;
            }
        }
        return  $ret;
    }
}

$my0rder= new OrdersStatus();

/*
$arFields['PAY_SYSTEM_ID']=2;
$_POST['ORDER_PROP_7']='Петров Александр Александрович';
$_POST['ORDER_PROP_6']='alexalexzolotuhin@gmail.com';
print_r($_POST);
if($arFields['PAY_SYSTEM_ID']==2 or $arFields['PAY_SYSTEM_ID']==9){

    $arEventFields = array(
        "NAME" => $_POST['ORDER_PROP_7'].'111111',
        "EMAIL_FROM_ORDER" => $_POST['ORDER_PROP_6']
    );
     CEvent::Send("PAYMENT_INSTRUCTIONS", 's1', $arEventFields);

}
 */

