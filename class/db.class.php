<?php

class DatabaseClass{
 
  
    private static $_sitekey,$_db,$_classObj,$_dsn, $_dbname,$_dbusername, $_pass, $_memcacheObj,$_website,$_sqlQuery;
    public static $sitedata;
    public static $_sayac = 0;
    public static $update_fields = array(),$insert_fields = array(),$status=array();
    
    private function __construct() {
       
        try {
            
            self::$_db = new PDO('mysql:host=' . self::$_dsn . ';dbname=' . self::$_dbname . '', self::$_dbusername, self::$_pass);
            self::$_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
        
        try{
            self::$_memcacheObj = new Memcache();
            self::$_memcacheObj->connect(MEMCACHE_HOST, MEMCACHE_PORT);
        }catch(Exception $e){
            throw new Exception ($e->getMessage(),$e->getCode());
        }
    }
   
   protected static function _generateSiteKey(){
       return md5(self::$_website.STATIC_MD5_KEY);
   } 
   
   public function isUpdateWebSites(){

       if(count(self::$update_fields)>0){
           return TRUE;
       }else{
           return FALSE;
       }
   }
   
   public function isinsertFields(){
       
       if(count(self::$insert_fields)>0){
           return TRUE;
       }else{
           return FALSE;
       }
   }
   protected static function _deleteMemcache($memcachekey){
      if (!isset($memcachekey)) {
            throw new Exception("Missing Key Parameter!");
        }

        if (self::$_memcacheObj->delete($memcachekey)) {
            return true;
        } else {
            return false;
        } 
   }
   
   
   public function insertWebSites(){
       
     
       $insert_query = "INSERT INTO `sites` (`id`, `webadress`, `total_time`, `order`) VALUES ";

       foreach(self::$insert_fields as $key=>$val){
           $insert_query.="(NULL,'".$val['website_url']."','".$val['total_time']."','null'),";
        }
        $insert_query = substr($insert_query, 0, -1);
        $insert_rows = self::$_db->exec($insert_query);
        self::_deleteMemcache(self::_generateSiteKey()); //delete memcache key
        //echo $insert_rows ." rows added!<br/>";
        self::$status["insertMessage"] = "Insert Fields :".$insert_rows;
   }
   
   public function updateWebSites(){

       $ids = array();
       $sql = "UPDATE sites SET total_time = (CASE ";

       foreach(self::$update_fields as $key => $val){
          
           array_push($ids,$val['website_id']);
           $sql.=" WHEN id = ".$val['website_id']." THEN '".$val['total_time']."'          
            ";
       }
       
      
     $sql.=" ELSE total_time END) WHERE id IN (".implode(",", $ids).")";

      $affected_rows = self::$_db->exec($sql);
      self::_deleteMemcache(self::$_sitekey); //delete memcache key
      self::$status["updateMessage"] = "Update Fields Count: ".$affected_rows;
      
   }
   
   

   /*
    * @desc: Site memcachede ise  sorgu atmadan update eder.
    * memcachede yoksa sql bakar.
    */
   public function isWebsiteMemcacheStore($websiteurl = "",$total_time = NULL){


        self::$_sayac = self::$_sayac+1;
 
        if(empty($websiteurl)){
            throw new Exception("Website url is empty",8);
        }
        self::$_website = $websiteurl;
        self::$_sitekey = self::_generateSiteKey();
        $cacheData = self::$_memcacheObj->get(self::$_sitekey);
        
        //self::_deleteMemcache(self::$_sitekey);
       
        if($cacheData){
            self::$update_fields[self::$_sayac]["website_id"] = $cacheData->id;
            self::$update_fields[self::$_sayac]["website_url"] = $cacheData->webadress;
            self::$update_fields[self::$_sayac]["total_time"] = $total_time;
            self::$update_fields[self::$_sayac]["order"] = $cacheData->order;
                
        }else{
            
            self::$sitedata = self::_isWebsiteMysqlStore();
          
            if(self::$sitedata){
                
              
                self::$update_fields[self::$_sayac]["website_id"] = self::$sitedata->id;
                self::$update_fields[self::$_sayac]["website_url"] = self::$sitedata->webadress;
                self::$update_fields[self::$_sayac]["total_time"] = $total_time;
                self::$update_fields[self::$_sayac]["order"] = self::$sitedata->order;
                
                /*
                 * DATAYI MEMCACHE AT
                 */
                self::_setMemcacheSiteUrl(self::$sitedata);
   
            }else{
                
                self::$insert_fields[self::$_sayac]["website_id"] = "null";
                self::$insert_fields[self::$_sayac]["website_url"] = self::$_website;
                self::$insert_fields[self::$_sayac]["total_time"] = $total_time; 
                $data = new stdClass();
                /*
                 * INSERT ANINDA MEMCACHE DATA ATABILIRIZ
                 */
                  
            } 
        } 
    }
   
   /*
    * @desc: Debug amacli execute edilen query string olarak gormek icin kullanilir.
    */
   public static function _debugQueryPrint(){
       return self::$_sqlQuery;
   }
   
   protected static function _setMemcacheSiteUrl($data){
     self::$_memcacheObj->add(self::_generateSiteKey(),$data,false,3600);  
   }
   /*
    * @desc: Web sitesi adresi Mysql de var mi ?
    */
   protected static function _isWebsiteMysqlStore(){
        
        $query = 'SELECT * FROM '.SITE_TABLE.' WHERE webadress =:webadress';
        $results = array();
        $sth = self::$_db->prepare($query);
        
        $params = array(":webadress" => self::$_website);
        $sth->execute($params);
        self::$_sqlQuery = $sth->queryString;
            return $sth->fetch(PDO::FETCH_OBJ);
   }
   

   public static function setup($dsn = "", $dbname = "", $dbusername = "", $pass = "") {
        self::$_dsn = $dsn;
        self::$_dbname = $dbname;
        self::$_dbusername = $dbusername;
        self::$_pass = $pass;
    
        if (!self::$_classObj) {
            self::$_classObj = new DatabaseClass();
        }
        return self::$_classObj;
    }
    
  public function printStatus(){
    return self::$status;
   }
}
?>
