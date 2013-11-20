<?php
require_once dirname(__FILE__)."/config.php";
require_once dirname(__FILE__)."/interface_structre.php";


$params = array( 
            array('webadress'=>'http://www.hacelis.com.tr'),
            array('webadress'=>'http://www.yahoo.com'), 
            array('webadress'=>'http://www.yazarbaz.com'),
            array('webadress'=>'http://www.mynet.com'),
            array('webadress'=>'http://www.r10.net'),
            array('webadress' => 'http://www.teknosa.com.tr')
        );

try{
 $class = new HttpWebRequest();
 $class->setUrls($params)->doRequest()->doSorting("ASC")->save();  
}catch(Exception $e){
    $output = json_encode(array("errorMessage" => $e->getMessage(),"errorNo" => $e->getCode()));
    echo $output;
}


?>
