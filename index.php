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
    const IBLOCK_ID=4;//id ���������
    const VERIFICATION=1;//������  ����� � csv � ����� ������� � ���
    public $UPDATE=0;

    public $MASS_FIELD=array();
    public $field_html_buffer='';
    private $_csv_file = null;
    private $error = array();
    private $array_line_full = array();
    function __construct($csv_file='svoistva.csv') {
        if (file_exists($csv_file)) { //���� ���� ����������
            $this->_csv_file = $csv_file; //���������� ���� � ����� � ����������
        }
        else { //���� ���� �� ������ �� �������� ����������
            throw new Exception("���� ".$csv_file." �� ������");
        }

        //PROPERTY_TYPE - L
        $this->MASS_FIELD=array(
         //   'CML2_ARTICLE'=>array('name'=>'�������'), //+������ � ��� � ������� �����
            '����� ���'=>array('name'=>'CML2_BAR_CODE'), //+��������
            '��� ���������'=>array('name'=>'P_PAN_TYPE','type'=>'radio'),
            '����� ���������'=>array('name'=>'P_PAN_SHAPE','type'=>'radio'), //2 ��������!!!
            '��� ��������'=>array('name'=>'P_SURFACETYPE','type'=>'radio'),
            '�����'=>array('name'=>'P_BRAND_LINK','type'=>'ib'), // � ��������� �������� ��������� ���������� -������
            '������ ������������'=>array('name'=>'P_PRODUCECOUNTRY'),
            '�������'=>array('name'=>'G_NEW','type'=>'checkbox'),
            '�����'=>array('name'=>'SERIES','type'=>'radio'),
            '��� ������'=>array('name'=>'P_TYPE_OLD'),
            '��� ������ (���)'=>array('name'=>'P_TYPE'),
            '�������, ��'=>array('name'=>'P_DIAMETER'),
            '������� ���, ��'=>array('name'=>'P_DIAMETERBOTTOM'),
            '�����'=>array('name'=>'P_VOLUME'),
            '��������'=>array('name'=>'P_STUFF'),
            '������� ������, ��'=>array('name'=>'P_WALL'),
            '������ ������, ��'=>array('name'=>'P_HEIGHTWALL'),
            '������'=>array('name'=>'P_ANGLEWALL','type'=>'radio'),
            '���������� �����'=>array('name'=>'P_HANDLEQNT'),//+
            '�������� �����'=>array('name'=>'P_HANDLEMATERIAL'),
            '������� ���, ��'=>array('name'=>'P_BOTTOM'),
            '���'=>array('name'=>'P_BOTTOMTYPE','type'=>'radio'),
            '������ � ���������'=>array('name'=>'P_LID' ,'type'=>'radio'), //� ������� ������
            '���������� ��������'=>array('name'=>'P_INCOVER'),//+
            '������� ��������'=>array('name'=>'P_OUTCOVER'),//+
            '���������� �� �'=>array('name'=>'P_TERM'),//+
            '���� �������'=>array('name'=>'P_OUTCOLOR'),
            '���� ������'=>array('name'=>'P_INCOLOR' , 'type'=>'radio'),
            '��� �����'=>array('name'=>'P_APPLY', 'type'=>'radio'),
            '������� (�����)'=>array('name'=>'P_GABARITES'),//+
            '������. ��������'=>array('name'=>'P_IFMETALLPOSSIBLE','type'=>'checkbox'),
            '�����������'=>array('name'=>'P_IFDISHWASHPOSSIBLE','type'=>'checkbox'), //�������. � �������. ������
            '�������'=>array('name'=>'P_IFOVENPOSSIBLE','type'=>'checkbox'),
            '������ �������'=>array('name'=>'P_HEAT_DETECTOR','type'=>'checkbox'),
            '������� �����'=>array('name'=>'P_REMOVHANDLE','type'=>'checkbox'),
            '�������� PTFE'=>array('name'=>'P_PTFEFREE','type'=>'checkbox'),//�������� ���
            '�������� PFOA'=>array('name'=>'P_PFOAFREE','type'=>'checkbox'),
            '��� ��������'=>array('name'=>'P_POTTYPE', 'type'=>'radio'),
            '��� ��������'=>array('name'=>'P_PACKTYPE', 'type'=>'radio'),
            '��������� �����'=>array('name'=>'P_KEEP_WHARM', 'type'=>'radio'),
            '��������� ����� � ������'=>array('name'=>'P_KEEP_COLD', 'type'=>'radio'),
            '���� ��������'=>array('name'=>'P_WARRANTYPER'),//+
            '�������'=>array('name'=>'SECTIONS', 'type'=>'other'),
            '������.������ ���������'=>array('name'=>'P_YM_CATEGORY'),//+
            '��� ����������'=>array('name'=>'P_ACCESSORYTYPE'),
            '��������� ��������'=>array('name'=>'DETAIL_TEXT', 'type'=>'other'), // � ������� Html
            '���'=>array('name'=>'RRC', 'type'=>'other' ),//��������
            'Google Merchant category'=>array('name'=>'GOOGLE_MERCHANT_CATEGORY'),
        );

        //�������� �������� �� ����������� ����, ��� ���� ��� �� ������, � ����� ����� ������!!!
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
            $this->MASS_FIELD['�����']['value'][$arFields['ID']]=$arFields['NAME'];
        }

        $arFilter = array('IBLOCK_ID' => self::IBLOCK_ID,); // ������� �������� ��� ����� ����������
        $rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'),$arFilter);
        while ($arSect = $rsSect->GetNext())
        {
            $this->MASS_FIELD['�������']['value'][$arSect['ID']]=$arSect['NAME'];
            // �������� ����������
        }
    }

    //��������� ������� �� �������
    function getButton()
    {
        $zap='';
        $arFilter = array(
            'IBLOCK_ID' => self::IBLOCK_ID, // ID ���������
            // ����� ������ ���������, �������� 'ACTIVE' => 'Y'
        );
        $res = CIBlockElement::GetList(false, $arFilter, array('IBLOCK_ID'));
        if ($el = $res->Fetch())
            $zap= '������� �������� �������� : '.$el['CNT'].' <br/>';
            $zap.= '���� : '.$this->_csv_file.' <br/>';

    return $zap.'
        <a href="/1c/?action=proverka">��������</a>
        <a href="/1c/?action=update">����������</a>';
    }
    function getBack()
    {
    return '
        <a href="/1c/">�����</a>
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

    //������� ��� ���� �� ������������
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


    /**�������� csvv� ��������� � ���������� ������. �.�.
     * � ��� ����� 3000 �������, �� ��� ����� ������,
     * ���� �� ������ 10 �����- ������ ����� ������ �� ������� �� � ���� ���� ��������� ��� ������������
     *
     */
    public function loadCSV() {
        $handle = fopen($this->_csv_file, "r"); //��������� csv ��� ������

        $array_line_full = array(); //������ ����� ������� ������ �� csv
        //�������� ���� csv-����, � ������ ���������. 3-�� �������� ����������� ����
        while (($line = fgetcsv($handle, 0, "^")) !== FALSE) {
            $array_line_full[] = $line; //���������� ������� � ������
        }
        fclose($handle); //��������� ����
        $this->array_line_full=$array_line_full;


        //����� �������� ����� ��������� �� ��������������� ����
       $mas_title_csv=$this->array_line_full[0];
        $mass_temp_title_csv_=array();//������� ������� ����������������  ������ , ��� ������������� ����

/*
        foreach ($this->MASS_FIELD as $key=>$value){
            $number_in_csv= array_search($value['name'],$mas_title_csv);
            $this->MASS_FIELD[$key]['number_in_csv']=$number_in_csv;
            $mass_temp_title_csv_[$key]=$value['name'];

        } */

        //������� ������� ��������
        $html='';
        $html.=$this->getStartTable();

        foreach ($mas_title_csv as $key=>$value){
            $number_in_csv= array_search($value,$mass_temp_title_csv_);
            if(empty ($number_in_csv)){
                $this->error[]='� ������� ����� ������� �� ������� ���� � ��������� :'.$value;
            }
            $row='';
            $row.=$this->getTdTable($key);
            $row.=$this->getTdTable($value);
            $text='<span style="color:red">�������� �� ���������</span>';
            $type='';
            //��������� ���� �� � ������� csv
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
        $this->field_html_buffer.= '<h1>���� � ��������</h1>';
        $this->field_html_buffer.= $html;
        /*
        if(self::VERIFICATION==1) {
            // �������� �� ������������� ��������
            // ���������, ��� �� ��� � ���������
             foreach ($mas_title_csv as $key=>$value){
                 $number_in_csv= array_search($value,$mass_temp_title_csv_);
                 if(empty ($number_in_csv)){
                     $this->error[]='� ������� ����� ������� �� ������� ���� � ��������� :'.$value;
                 }
            }
            if(!empty ($this->error)){
                debug($this->error);
                die();
            }
        } */
       //��������� ��������� ����

    }

    /** �������� ������ �� ���������, ���� ��� ������, �� ������ ������, ���� ���, �� false
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
    * �������� �� ������ ������� csv �������� ��������
    *������� ������� ������, ��� ��������� � � ��� �����������,
    * ��� ��������� ������ ��� � ����
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

    //�������� �� ������ �������, ��� ������, ��� ��� �������� ��� ������, ���, ��� ��������
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
    //�������� �������� ������
    function getProverkaTovara($SKOD, $csv_massiv){//6951258910310
        $mass_error=array();
        $mass_success=array();

        //��� ��� ������ ��������� ������!!! ������ ���.
        $data=$this->getProductBySKod($SKOD);

        $flag_fatal=false;
        if(empty($SKOD)){
            $mass_error[] ="�������� ������!!!";
            $flag_fatal=true;
        }
        if(empty($data)){
            $mass_error[] = '����� �� ������ �� �����';
            $flag_fatal=true;
        }

        if( $flag_fatal==false){ //���� ��� �������� ������ �������� �������� ��� ����������
            ///
            ///  //�������� �� csv �������, � ��� ��� ��������������, ���� �������� �����
            //debug( $this->MASS_FIELD);
            foreach ($csv_massiv as $num_row=>$value){
                //�������� ������ �� �����
                $name_property= $this->getByNumRowNameProperty($num_row);
                $data_property= $this->getByNumRowDataProperty($num_row);
                $name_row=$data_property['name_row'];
                // ��������� ��������
                //��������� ��������-  ��� �� ���������
                if($name_property=='CML2_BAR_CODE'){
                    continue;
                }
                //�������, ��������� , ��������� ����


                $el_PROPERTY=$data['PROPERTY'][$name_property];

                if($data_property['type']=='str'){//������!!
                    if(empty($el_PROPERTY['VALUE'])){
                        $mass_success[] = $name_row.'-�����, ���������. � ��������:'.$value;
                        if($this->UPDATE){
                            CIBlockElement::SetPropertyValueCode($data['DATA']['ID'], $name_property, $value );
                        }
                    }else{
                        $mass_error[]=$name_row.'- '.$el_PROPERTY['VALUE'].' -��� ���������';
                    }
                }
                // ��������� ������� � ����������


               if($data_property['type']=='radio' or $data_property['type']=='ib'){

                    if(!empty($el_PROPERTY['VALUE'])){
                        $mass_error[]=$name_row.'- '.$value.' -���� ��� ���������!!!';
                    }else{
                        $mas_list=$this->MASS_FIELD[$name_row]['value'];
                        //  debug($mas_list);
                        //������������������� �����
                        //debug($mas_list);
                        $number_list = array_search(strtolower($value), array_map('strtolower',$mas_list));
                        if(!empty($number_list )){
                            $mass_success[] = $name_row.' '.$name_property.'-�����, ���������.id ������='.$number_list.'��������:'.$mas_list[$number_list].' � ��������:'.$value;
                            if($this->UPDATE){
                                $ret=CIBlockElement::SetPropertyValueCode($data['DATA']['ID'], $name_property, $number_list );
                                if($ret){

                                }
                            }
                        }else{
                                $mass_error[]=$name_row.'- '.$value.' -�������� �� ������� ����� �������!!!';
                        }


                    }


                }



                $el_PROPERTY=$data['PROPERTY'][$name_property];
                if($data_property['type']=='checkbox'){//������!!
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
                                $mass_success[] = $name_row.'- �������� �������:'.$el_PROPERTY['VALUE'].' � ��������:'.$value;
                            }else{
                                $mass_error[]=$name_row.'- '.$value.' -����������� ������ � ��������!!!';
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
                             $mass_success[] = $name_row.'- ��������� �������� ���������:';
                         }else{
                             $mass_error[] = ' ��������� �������� ��� ����!:';
                         }

                      }
                    if($name_property=='SECTIONS'){
                        $SECTION_NAMES=$this->MASS_FIELD['�������']['value'];
                        $mass_section=explode("|",$value);
                        $mass_key=array();
                        //���� �� �������, ������� ������� ���������� � �������� � ������ ��� ����������
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
                            $mass_success[] = $name_row.'- ������� ���������';
                            }else{
                                $mass_error[] = ' ������� ��� ���������:';
                            }
                        }
                    }


                    if($name_property=='RRC') {
                        $price = CPrice::GetBasePrice($data['DATA']['ID']);
                     //   debug($price['PRICE']);
                      //  debug($price);
                        if(!empty($price)){
                            //
                            $mass_error[] = $name_row.'- ���� ������ ����:'.$price['PRICE'];
                        }else{
                            if($this->UPDATE){
                                CPrice::SetBasePrice($data['DATA']['ID'], $value, 'RUB');
                            }
                            $mass_success[] = $name_row.'- ���������� ����:'.$value;
                        }
                    }
                }

            }

            if(empty($data['DATA']['CODE'])) {
                $mass_success[] = '��������� ���������� ��� ';
                $params = Array(
                    "max_len" => "100", // �������� ���������� ��� �� 100 ��������
                    "change_case" => "L", // ����� ������������� � ������� ��������
                    "replace_space" => "_", // ������ ������� �� ������ �������������
                    "replace_other" => "_", // ������ ����� ������� �� ������ �������������
                    "delete_repeat_replace" => "true", // ������� ������������� ������ �������������
                    "use_google" => "false", // ��������� ������������� google
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
            $text.='<p class="show_error" style="color:red">�������� ������</p><div style="display: none;">';
            foreach ($mass_error as $value){
                $text.='<span style="color:red">'.$value.'</span><br/>';
            }
            $text.="</div>";
        }
        if(!empty($mass_success)){
            $text.='<p  class="show_success"  style="color:green">�������� ������</p><div style="display: none;">';
            foreach ($mass_success as $value){
                $text.='<span style="color:green">'.$value.'</span><br/>';
            }
            $text.="</div>";
        }

        return $text;
    }

    //�������� Html �� ��������� ��������
    function getHtmlProverka(){
        //��������� ������� �� ������� ����� � ��������
        //��������� � ������� ����� ����������  ������ ��� � ��������,
        //���������, � ���� �� ��� � �������???
        //$this->MASS_FIELD
        $html=' <h1>�������� ������ �� ���������</h1>';
        $html.=$this->getStartTable();
        $row='';
        $row.=$this->getTdTable('�������');
        $row.=$this->getTdTable('��������');
        $row.=$this->getTdTable('�����-���');
        $row.=$this->getTdTable('�������� ��������' );
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
            $row.=$this->getTdTable($CML2_ARTICLE);// �������
            $row.=$this->getTdTable($NAME);

            $row.=$this->getTdTable($SCOD);
            $text=$this->getProverkaTovara($SCOD ,$value);// �� ���������
            $row.=$this->getTdTable($text,'svoistva');
            $html.=$this->getRowTable($row);
        }
        $html.=$this->getEndTable();
        return $html;
    }
}

$csv=new LoadCsv();

//����������, �� �������
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