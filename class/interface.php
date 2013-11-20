<?php

interface InterFaceHttpWebRequest{
 
    
 public function setUrls($params);   
 public function doRequest();
 public function doSorting($sorting);
     

}

interface Request{
    public function executeRequest(InterFaceHttpWebRequest $inter);
}

?>
