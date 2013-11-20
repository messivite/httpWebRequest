<?php

class DoRequest implements Request{
    public $cn;
    public $ret;
    CONST CURL_EXTENSION_ERROR = "Couldn't initialize a cURL handle";
    
    protected function _closeConnection(){
        curl_close($this->ch);
    }
    
    public function __destruct() {
        curl_close($this->ch);
    }
    protected function _connectWebAdress($url = ""){
        
        $this->ret = curl_setopt($this->ch, CURLOPT_URL,            $url);
        $this->ret = curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
        $this->ret = curl_exec($this->ch);
        
        if(empty($this->ret)){
            return NULL;
        }else{
            $info = curl_getinfo($this->ch);
            return $info["total_time"];
        }
        
        
    }
    
    public function executeRequest(InterFaceHttpWebRequest $http) {
        
         try{
            $this->ch = curl_init();  
        }catch(Exception $e){
            throw new Exception(self::CURL_EXTENSION_ERROR,2);
        }
        
        foreach($http->params as $index=> $ar){
            foreach ($ar as $key=>$val){
                $http->params[$index]["total_point"] =(string)$this->_connectWebAdress($val) ;
            }
        }
    }
}


class HttpWebRequest implements InterFaceHttpWebRequest{
    
    public $params;
    public $request;
    public $sort = array("DESC" => SORT_DESC,"ASC" => SORT_ASC);
    
    CONST PLEASE_SET_URL_PARAMS = "Please set to urls params!";
    CONST INVALID_SORT_PARAMETER = "Invalid sort parameter!";
    
    public function setUrls($params = array()){
        
        $this->params = $params;

        if($this->_isParamValid()){
            return $this;
        }
  
    }
    public function doRequest(){
       
        $this->request = new DoRequest();
        $this->_executeConnect($this->request,$this->params);
        return $this;
    }
    
    public function doSorting($sorting = "ASC"){
        
        if(!isset($this->sort[$sorting])){
            throw new Exception($sorting ." is ".self::INVALID_SORT_PARAMETER,3);
        }
        foreach ($this->params as $key => $row) {
            $new_sorting[$key] = $row['total_point'];
        }
        
        array_multisort($new_sorting, $this->sort[$sorting],$this->params);
        return $this;        
    }
    
    
    public function save(){
        
        $dbObj = DatabaseClass::setup(MYSQL_HOST, MYSQL_DB, MYSQL_USER, MYSQL_PASS);
  
        foreach ($this->params as $key=>$val){
               $dbObj->isWebsiteMemcacheStore($this->params[$key]["webadress"],$this->params[$key]["total_point"]);

        }
        if($dbObj->isUpdateWebSites()){
            $dbObj->updateWebSites();
        }
        
        if($dbObj->isinsertFields()){
            $dbObj->insertWebSites();
        };

        echo json_encode($dbObj->printStatus());
        return $this;
    }
    
    
    protected function _executeConnect(Request $request){
         $request->executeRequest($this);
    }
    
    protected function _isParamValid(){
        if(!is_array($this->params) || count($this->params) === 0 ){
            throw new Exception(self::PLEASE_SET_URL_PARAMS,1);
        }
        return true;
    }
    
}
?>
