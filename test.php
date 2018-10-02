<?php
//на пряму работает!!!

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>

<?$APPLICATION->IncludeComponent("bitrix:sale.order.payment.receive","",Array(
"PAY_SYSTEM_ID" => "9",
"PERSON_TYPE_ID" => "1"
)
);?>
<?php

/*
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("sale");
CModule::IncludeModule("iblock");
CModule::IncludeModule("catalog");

echo $_SERVER['HTTP_HOST'];
die();


$arFields = Array(

    "ORDER_ID" => '23942',
    "ORDER_DATE" => 'ORDE date 2',
    "ORDER_USER" => 'ORDER_USER3333',
    "PRICE" => '33113',
    "BCC" => '', //пусто

    "ORDER_LIST" => 'sfsdfsdf',

    "EMAIL" => 'alexalexzolotuhin@yandex.ru',
    "SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@t.skovoroda1.webtm.ru"),

);
$eventName = "SALE_NEW_ORDER";

print_r($arFields);

$bSend = true;


if($bSend)
{
    $event = new CEvent;
    $event->Send($eventName, 's1', $arFields, "Y" );
    //  print_r($event);
} */

?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
