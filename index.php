<?php
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
CModule::IncludeModule('iblock');
CModule::IncludeModule("catalog");
/*
function debug($var){
    echo '<pre>';
    print_r($var);
    echo '</pre>';
} */




class LoadCsv {
    const IBLOCK_ID=4;//id инфоблока
    const VERIFICATION=1;//сверка  полей в csv и полей забитых у нас
    public $UPDATE=0;

    public $MASS_FIELD=array();
    public $field_html_buffer='';
    private $_csv_file = null;
    private $error = array();
    private $array_line_full = array();
    function __construct($csv_file='svoistva.csv') {
        if (file_exists($csv_file)) { //Если файл существует
            $this->_csv_file = $csv_file; //Записываем путь к файлу в переменную
        }
        else { //Если файл не найден то вызываем исключение
            throw new Exception("Файл ".$csv_file." не найден");
        }

        //PROPERTY_TYPE - L
        $this->MASS_FIELD=array(
         //   'CML2_ARTICLE'=>array('name'=>'Артикул'), //+пробел и Код с большой буквы
            'Штрих код'=>array('name'=>'CML2_BAR_CODE'), //+исключен
            'Тип сковороды'=>array('name'=>'P_PAN_TYPE','type'=>'radio'),
            'Форма сковороды'=>array('name'=>'P_PAN_SHAPE','type'=>'radio'), //2 свойства!!!
            'Тип покрытия'=>array('name'=>'P_SURFACETYPE','type'=>'radio'),
            'Бренд'=>array('name'=>'P_BRAND_LINK','type'=>'ib'), // к инфоблоку привязка Служебная информация -бренды
            'Страна производства'=>array('name'=>'P_PRODUCECOUNTRY'),
            'Новинка'=>array('name'=>'G_NEW','type'=>'checkbox'),
            'Серия'=>array('name'=>'SERIES','type'=>'radio'),
            'Тип товара'=>array('name'=>'P_TYPE_OLD'),
            'Тип товара (рус)'=>array('name'=>'P_TYPE'),
            'Диаметр, см'=>array('name'=>'P_DIAMETER'),
            'Диаметр дна, см'=>array('name'=>'P_DIAMETERBOTTOM'),
            'Объём'=>array('name'=>'P_VOLUME'),
            'Материал'=>array('name'=>'P_STUFF'),
            'Толщина стенок, мм'=>array('name'=>'P_WALL'),
            'Высота стенки, см'=>array('name'=>'P_HEIGHTWALL'),
            'Стенки'=>array('name'=>'P_ANGLEWALL','type'=>'radio'),
            'Количество ручек'=>array('name'=>'P_HANDLEQNT'),//+
            'Материал ручки'=>array('name'=>'P_HANDLEMATERIAL'),
            'Толщина дна, мм'=>array('name'=>'P_BOTTOM'),
            'Дно'=>array('name'=>'P_BOTTOMTYPE','type'=>'radio'),
            'Крышка в комплекте'=>array('name'=>'P_LID' ,'type'=>'radio'), //в выгрузе чебокс
            'Внутреннее покрытие'=>array('name'=>'P_INCOVER'),//+
            'Внешнее покрытие'=>array('name'=>'P_OUTCOVER'),//+
            'Нагревание до С'=>array('name'=>'P_TERM'),//+
            'Цвет снаружи'=>array('name'=>'P_OUTCOLOR'),
            'Цвет внутри'=>array('name'=>'P_INCOLOR' , 'type'=>'radio'),
            'Тип плиты'=>array('name'=>'P_APPLY', 'type'=>'radio'),
            'Размеры (ДхШхВ)'=>array('name'=>'P_GABARITES'),//+
            'Металл. предметы'=>array('name'=>'P_IFMETALLPOSSIBLE','type'=>'checkbox'),
            'Посудомойка'=>array('name'=>'P_IFDISHWASHPOSSIBLE','type'=>'checkbox'), //Использ. в посудом. машине
            'Духовка'=>array('name'=>'P_IFOVENPOSSIBLE','type'=>'checkbox'),
            'Датчик нагрева'=>array('name'=>'P_HEAT_DETECTOR','type'=>'checkbox'),
            'Съёмная ручка'=>array('name'=>'P_REMOVHANDLE','type'=>'checkbox'),
            'Содержит PTFE'=>array('name'=>'P_PTFEFREE','type'=>'checkbox'),//значение НЕТ
            'Содержит PFOA'=>array('name'=>'P_PFOAFREE','type'=>'checkbox'),
            'Тип кастрюли'=>array('name'=>'P_POTTYPE', 'type'=>'radio'),
            'Тип упаковки'=>array('name'=>'P_PACKTYPE', 'type'=>'radio'),
            'Удержание тепла'=>array('name'=>'P_KEEP_WHARM', 'type'=>'radio'),
            'Удержание тепла в холоде'=>array('name'=>'P_KEEP_COLD', 'type'=>'radio'),
            'Срок гарантии'=>array('name'=>'P_WARRANTYPER'),//+
            'Разделы'=>array('name'=>'SECTIONS', 'type'=>'other'),
            'Яндекс.Маркет категория'=>array('name'=>'P_YM_CATEGORY'),//+
            'Тип аксессуара'=>array('name'=>'P_ACCESSORYTYPE'),
            'Детальное описание'=>array('name'=>'DETAIL_TEXT', 'type'=>'other'), // в формате Html
            'РРЦ'=>array('name'=>'RRC', 'type'=>'other' ),//заменено
            'Google Merchant category'=>array('name'=>'GOOGLE_MERCHANT_CATEGORY'),
        );

        //получаем свойства по символьному коду, для того что бы занать, в каких полях список!!!
        foreach($this->MASS_FIELD as $key=>$val){
            $property_enums = CIBlockPropertyEnum::GetList(Array("DEF"=>"DESC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>self::IBLOCK_ID, "CODE"=>$val['name']));

          //  echo '----------'.$key.'------------<br/>';
            while($enum_fields = $property_enums->GetNext())
            {
            //    echo $enum_fields["ID"]." - ".$enum_fields["VALUE"]."<br>";
                $this->MASS_FIELD[$key]['value'][$enum_fields["ID"]]=$enum_fields["VALUE"];
            }
           // echo '----------------------<br/>';
        }

        $arSelect = Array("ID", "NAME");
        $arFilter = Array("IBLOCK_ID"=>23,  "ACTIVE"=>"Y");
        $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
        while($ob = $res->GetNextElement())
        {
            $arFields = $ob->GetFields();
            //print_r($arFields);
            $this->MASS_FIELD['Бренд']['value'][$arFields['ID']]=$arFields['NAME'];
        }

        $arFilter = array('IBLOCK_ID' => self::IBLOCK_ID,); // выберет потомков без учета активности
        $rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'),$arFilter);
        while ($arSect = $rsSect->GetNext())
        {
            $this->MASS_FIELD['Разделы']['value'][$arSect['ID']]=$arSect['NAME'];
            // получаем подразделы
        }
    }

    //несколько функций по дизайну
    function getButton()
    {
        $zap='';
        $arFilter = array(
            'IBLOCK_ID' => self::IBLOCK_ID, // ID инфоблока
            // любые другие параметры, например 'ACTIVE' => 'Y'
        );
        $res = CIBlockElement::GetList(false, $arFilter, array('IBLOCK_ID'));
        if ($el = $res->Fetch())
            $zap= 'Записей торговом каталоге : '.$el['CNT'].' <br/>';
            $zap.= 'Файл : '.$this->_csv_file.' <br/>';

    return $zap.'
        <a href="/1c/?action=proverka">Проверка</a>
        <a href="/1c/?action=update">Обновление</a>';
    }
    function getBack()
    {
    return '
        <a href="/1c/">Назад</a>
      ';
    }
    function getStartTable()
    {
        return  '<table class="table_csv">';
    }
    function getEndTable()
    {
       return  '</table>';

    }
    function getRowTable($content)
    {
        return '<tr>'.$content.'</tr>';
    }
    function getTdTable($content, $class='')
    {
        return '<td class="'.$class.'" style="vertical-align: top;">'.$content.'</td>';
    }

    //выводит все поля из конструктора
    function getHtmlMassFields()
    {
        $ret=$this->getStartTable();
        $row='';
        foreach ($this->MASS_FIELD as $key=>$val){
            $row.=  $this->getTdTable($key);
        }
        $ret.=$this->getRowTable($row);
        $ret.=$this->getEndTable();
        return $ret;
    }


    /**получаем csvvи загружаем в переменную класса. Т.к.
     * У нас всего 3000 записей, то так можно делать,
     * было бы больше 10 тысяч- скорее всего памяти не хватило бы и надо было построчно все обрабатывать
     *
     */
    public function loadCSV() {
        $handle = fopen($this->_csv_file, "r"); //Открываем csv для чтения

        $array_line_full = array(); //Массив будет хранить данные из csv
        //Проходим весь csv-файл, и читаем построчно. 3-ий параметр разделитель поля
        while (($line = fgetcsv($handle, 0, "^")) !== FALSE) {
            $array_line_full[] = $line; //Записываем строчки в массив
        }
        fclose($handle); //Закрываем файл
        $this->array_line_full=$array_line_full;


        //после загрузки сразу проверяем на соответствующие поля
       $mas_title_csv=$this->array_line_full[0];
        $mass_temp_title_csv_=array();//попутно создаем конвертированный  массив , для использования ниже

/*
        foreach ($this->MASS_FIELD as $key=>$value){
            $number_in_csv= array_search($value['name'],$mas_title_csv);
            $this->MASS_FIELD[$key]['number_in_csv']=$number_in_csv;
            $mass_temp_title_csv_[$key]=$value['name'];

        } */

        //выводим таблицу сравненя
        $html='';
        $html.=$this->getStartTable();

        foreach ($mas_title_csv as $key=>$value){
            $number_in_csv= array_search($value,$mass_temp_title_csv_);
            if(empty ($number_in_csv)){
                $this->error[]='В массиве полей скрипта на найдено поле с названием :'.$value;
            }
            $row='';
            $row.=$this->getTdTable($key);
            $row.=$this->getTdTable($value);
            $text='<span style="color:red">Свойство не объявлено</span>';
            $type='';
            //проверяем если ли в массиве csv
            if(array_key_exists ( $value,  $this->MASS_FIELD )){
                $text=  $this->MASS_FIELD[$value]['name'];
                $this->MASS_FIELD[$value]['number_in_csv']=$key;
                if(isset($this->MASS_FIELD[$value]['type'])){
                    $type=$this->MASS_FIELD[$value]['type'];
                }else{
                    $type= $this->MASS_FIELD[$value]['type']='str';
                }
            }
            $row.=$this->getTdTable($text);
            $row.=$this->getTdTable($type);
            $html.=$this->getRowTable($row);
        }
        $html.=$this->getEndTable();

     //  debug($this->MASS_FIELD);
        $this->field_html_buffer.= '<h1>Поля в выгрузке</h1>';
        $this->field_html_buffer.= $html;
        /*
        if(self::VERIFICATION==1) {
            // проверка на сосответствие выгрузки
            // проверяем, что не так с выгрузкой
             foreach ($mas_title_csv as $key=>$value){
                 $number_in_csv= array_search($value,$mass_temp_title_csv_);
                 if(empty ($number_in_csv)){
                     $this->error[]='В массиве полей скрипта на найдено поле с названием :'.$value;
                 }
            }
            if(!empty ($this->error)){
                debug($this->error);
                die();
            }
        } */
       //проверяем проверяем поля

    }

    /** получаем данные по штрихкоду, если все хорошо, то отдаем массив, если нет, то false
     * @param $SKOD
     * @return massiv or false
     */
    function getProductBySKod($SKOD){

        $ret=false;
        $arSort = array('SORT' => 'ASC', 'ID' => 'DESC');
        $arFilter = array('ACTIVE' => 'Y', 'PROPERTY_CML2_BAR_CODE'=>$SKOD, 'IBLOCK_ID' => self::IBLOCK_ID);
        $arSelect = array('ID', 'NAME', 'DETAIL_PAGE_URL' ,'DETAIL_TEXT' ,'IBLOCK_SECTION_ID' ,'PROPERTY_*');
        $res = CIBlockElement::getList($arSort, $arFilter, false, array( 'nPageSize' => 1), $arSelect);
        if ($row = $res->GetNextElement()) {
            // debug($row);
            $ret=array();
            $ret['DATA']=$row->fields;
            $ret['PROPERTY'] = $row->GetProperties();
        }

        return $ret;
    }

    /**
    * получаем по номеру столбца csv название свойства
    *вначале обходим массив, что загрузили и в нем вытаскиваем,
    * без обращения каждый раз к базе
    *
    **/
    function getByNumRowNameProperty($num_row){
        $return='';
        foreach ($this->MASS_FIELD as $key=>$val){
            if($val['number_in_csv']==$num_row){
                $return=$val['name'];
            }
        }
        //debug($this->MASS_FIELD);
        return $return;
    }

    //получаем по номеру столбца, все данные, как имя свойства для записи, тип, его название
    function getByNumRowDataProperty($num_row){
        $return='';
        foreach ($this->MASS_FIELD as $key=>$val){
            if($val['number_in_csv']==$num_row){
                $return=$val;
                $return['name_row']=$key;
            }
        }
        //debug($this->MASS_FIELD);
        return $return;
    }
    //получаем проверку товара
    function getProverkaTovara($SKOD, $csv_massiv){//6951258910310
        $mass_error=array();
        $mass_success=array();

        //тут все данные найденого товара!!! вообще все.
        $data=$this->getProductBySKod($SKOD);

        $flag_fatal=false;
        if(empty($SKOD)){
            $mass_error[] ="Штрихкод пустой!!!";
            $flag_fatal=true;
        }
        if(empty($data)){
            $mass_error[] = 'Товар не найден на сайте';
            $flag_fatal=true;
        }

        if( $flag_fatal==false){ //если нет фатально ошибки проходим проверку или наполнение
            ///
            ///  //проходим по csv массиву, и там где несоответствие, даем обратную связь
            //debug( $this->MASS_FIELD);
            foreach ($csv_massiv as $num_row=>$value){
                //возможно функця не нужна
                $name_property= $this->getByNumRowNameProperty($num_row);
                $data_property= $this->getByNumRowDataProperty($num_row);
                $name_row=$data_property['name_row'];
                // проверяем свойство
                //пропустим штрихкод-  его не заполняем
                if($name_property=='CML2_BAR_CODE'){
                    continue;
                }
                //Обновим, провермим , строковые типы


                $el_PROPERTY=$data['PROPERTY'][$name_property];

                if($data_property['type']=='str'){//строка!!
                    if(empty($el_PROPERTY['VALUE'])){
                        $mass_success[] = $name_row.'-Пусто, заполняем. В выгрузке:'.$value;
                        if($this->UPDATE){
                            CIBlockElement::SetPropertyValueCode($data['DATA']['ID'], $name_property, $value );
                        }
                    }else{
                        $mass_error[]=$name_row.'- '.$el_PROPERTY['VALUE'].' -Уже заполнено';
                    }
                }
                // обработка списков и инфоблоков


               if($data_property['type']=='radio' or $data_property['type']=='ib'){

                    if(!empty($el_PROPERTY['VALUE'])){
                        $mass_error[]=$name_row.'- '.$value.' -Поле уже заполнено!!!';
                    }else{
                        $mas_list=$this->MASS_FIELD[$name_row]['value'];
                        //  debug($mas_list);
                        //регистронезависимый поиск
                        //debug($mas_list);
                        $number_list = array_search(strtolower($value), array_map('strtolower',$mas_list));
                        if(!empty($number_list )){
                            $mass_success[] = $name_row.' '.$name_property.'-Пусто, заполняем.id списка='.$number_list.'Значение:'.$mas_list[$number_list].' В выгрузке:'.$value;
                            if($this->UPDATE){
                                $ret=CIBlockElement::SetPropertyValueCode($data['DATA']['ID'], $name_property, $number_list );
                                if($ret){

                                }
                            }
                        }else{
                                $mass_error[]=$name_row.'- '.$value.' -Значение не найдено среди списков!!!';
                        }


                    }


                }



                $el_PROPERTY=$data['PROPERTY'][$name_property];
                if($data_property['type']=='checkbox'){//строка!!
                            if($value=='1' or  $value=='0' ){
                                if($this->UPDATE) {
                                    $first_key=null;
                                    if($value==1){
                                        $mas_list=$this->MASS_FIELD[$name_row]['value'];
                                        foreach ($mas_list as $key=>$value){
                                            $first_key=$key;
                                            break;
                                        }
                                    }
                                    CIBlockElement::SetPropertyValueCode($data['DATA']['ID'], $name_property, $first_key );
                                }
                                $mass_success[] = $name_row.'- Значение чебокса:'.$el_PROPERTY['VALUE'].' В выгрузке:'.$value;
                            }else{
                                $mass_error[]=$name_row.'- '.$value.' -Неизвестный символ в выгрузке!!!';
                            }
                }

                if($data_property['type']=='other'){
                    $arLoadProductArray=array();

                     if($name_property=='DETAIL_TEXT'){
                         if(empty($data['DATA']['DETAIL_TEXT'])){
                             $arLoadProductArray = Array(
                                 "DETAIL_TEXT"=>$value,
                             );
                             $el_obj = new CIBlockElement;
                             if($this->UPDATE){
                                 $res = $el_obj->Update($data['DATA']['ID'], $arLoadProductArray );
                             }
                             $mass_success[] = $name_row.'- Детальное описание заполнено:';
                         }else{
                             $mass_error[] = ' Детальное описание уже есть!:';
                         }

                      }
                    if($name_property=='SECTIONS'){
                        $SECTION_NAMES=$this->MASS_FIELD['Разделы']['value'];
                        $mass_section=explode("|",$value);
                        $mass_key=array();
                        //ищем по секциям, которые заранее загрузиили и помещаем в массив для сохранения
                        foreach ($mass_section as $section_item) {
                            $key=array_search($section_item ,$SECTION_NAMES);
                            if(!empty($key)){
                                $mass_key[]=$key;
                            }
                        }

                        if(!empty($mass_key)){
                            if(empty($data['DATA']['IBLOCK_SECTION_ID'])){
                            $arLoadProductArray = Array(
                                "IBLOCK_SECTION"=>$mass_key,
                            );
                            $el_obj = new CIBlockElement;
                            if($this->UPDATE){
                                $res = $el_obj->Update($data['DATA']['ID'], $arLoadProductArray );
                            }
                            $mass_success[] = $name_row.'- Разделы обновлены';
                            }else{
                                $mass_error[] = ' Разделы уже заполнены:';
                            }
                        }
                    }


                    if($name_property=='RRC') {
                        $price = CPrice::GetBasePrice($data['DATA']['ID']);
                     //   debug($price['PRICE']);
                      //  debug($price);
                        if(!empty($price)){
                            //
                            $mass_error[] = $name_row.'- Есть старая цена:'.$price['PRICE'];
                        }else{
                            if($this->UPDATE){
                                CPrice::SetBasePrice($data['DATA']['ID'], $value, 'RUB');
                            }
                            $mass_success[] = $name_row.'- Выставлена цена:'.$value;
                        }
                    }
                }

            }

            if(empty($data['DATA']['CODE'])) {
                $mass_success[] = 'Выставлен символьный код ';
                $params = Array(
                    "max_len" => "100", // обрезает символьный код до 100 символов
                    "change_case" => "L", // буквы преобразуются к нижнему регистру
                    "replace_space" => "_", // меняем пробелы на нижнее подчеркивание
                    "replace_other" => "_", // меняем левые символы на нижнее подчеркивание
                    "delete_repeat_replace" => "true", // удаляем повторяющиеся нижние подчеркивания
                    "use_google" => "false", // отключаем использование google
                );
                $arLoadProductArray = Array(
                    "CODE" => CUtil::translit($data['DATA']['NAME'], "ru" , $params),
                );
                $el_obj = new CIBlockElement;
                if($this->UPDATE){
                    $res = $el_obj->Update($data['DATA']['ID'], $arLoadProductArray );
                }

            }
        }

        $text='';

        if(!empty($mass_success)){
            $text.='<p class="show_error" style="color:red">Показать ошибки</p><div style="display: none;">';
            foreach ($mass_error as $value){
                $text.='<span style="color:red">'.$value.'</span><br/>';
            }
            $text.="</div>";
        }
        if(!empty($mass_success)){
            $text.='<p  class="show_success"  style="color:green">Показать успехи</p><div style="display: none;">';
            foreach ($mass_success as $value){
                $text.='<span style="color:green">'.$value.'</span><br/>';
            }
            $text.="</div>";
        }

        return $text;
    }

    //получаем Html со значениям проверки
    function getHtmlProverka(){
        //проверяем вначале на наличие полей в выгрузке
        //добавляем к массиву полей порядковые  номера как в выгрузке,
        //проверяем, а есть ли все в наличии???
        //$this->MASS_FIELD
        $html=' <h1>Проверка товара по штрихкоду</h1>';
        $html.=$this->getStartTable();
        $row='';
        $row.=$this->getTdTable('Артикул');
        $row.=$this->getTdTable('Название');
        $row.=$this->getTdTable('Штрих-код');
        $row.=$this->getTdTable('Свойства статусов' );
        $html.=$this->getRowTable($row);

        foreach ($this->array_line_full as $key=>$value){
            if($key==0){continue; }

            //var_dump($value[0]);

            $SCOD=$value[0];
          //  echo '------1----';
          //  echo $SCOD;
            $data=$this->getProductBySKod($SCOD);


            $NAME=$data['DATA']['NAME'];

            //echo $NAME;
            //echo '------2----';
            $CML2_ARTICLE=$data['PROPERTY']['CML2_ARTICLE']['VALUE'];

            $row='';
            $row.=$this->getTdTable($CML2_ARTICLE);// артикул
            $row.=$this->getTdTable($NAME);

            $row.=$this->getTdTable($SCOD);
            $text=$this->getProverkaTovara($SCOD ,$value);// по штрихкоду
            $row.=$this->getTdTable($text,'svoistva');
            $html.=$this->getRowTable($row);
        }
        $html.=$this->getEndTable();
        return $html;
    }
}

$csv=new LoadCsv();

//контроллер, по кнопкам
$action=isset($_GET['action'])?$_GET['action']:'default';
switch ($action){
    case 'default':
        echo $csv->getButton();
        break;
    case 'proverka':
        echo $csv->getBack();
        $csv->UPDATE=0;
        $csv->loadCSV();
        echo $csv->getHtmlProverka();
        echo $csv->field_html_buffer;
       // echo $csv->getHtmlMassFields();
        break;
    case 'update':
        echo $csv->getBack();
        $csv->UPDATE=1;
        $csv->loadCSV();
        echo $csv->getHtmlProverka();
        echo $csv->field_html_buffer;
        break;
}

?>

    <style>
        .table_csv .svoistva{
            width:500px;

        }
        .table_csv {
            width:100%;
            border-spacing: 0px;
            border-collapse: collapse;
        }
        .table_csv td{
            border: 1px solid #d6d7d7;
            padding: 3px 5px;
        }

    </style>

    <script>
        jQuery(document).ready(function() {
            jQuery( ".show_error, .show_success" ).click(function(e) {
                e.preventDefault();
                jQuery( this ).next().toggle();
            });
        });
    </script>
<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");