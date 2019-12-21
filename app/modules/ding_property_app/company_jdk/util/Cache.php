<?php
namespace  app\modules\ding_property_app\company_jdk\util;
use Yii;
class Cache
{
    public static function setSuiteTicket($ticket)
    {
        Yii::$app->cache->set("suite_ticket", $ticket);
    }
    
    public static function getSuiteTicket()
    {
        return Yii::$app->cache->get("suite_ticket");
    }
    
    public static function setJsTicket($key,$ticket)
    {
        Yii::$app->cache->set($key, $ticket, 7000); // js ticket有效期为7200秒，这里设置为7000秒
    }
    
    public static function getJsTicket($key)
    {
        return Yii::$app->cache->get($key);
    }
    
    public static function setSuiteAccessToken($accessToken)
    {
        Yii::$app->cache->set("suite_access_token", $accessToken, 7000); // suite access token有效期为7200秒，这里设置为7000秒
    }
    
    public static function getSuiteAccessToken()
    {
        return Yii::$app->cache->get("suite_access_token");
    }

    public static function setIsvCorpAccessToken($key,$accessToken)
    {
        Yii::$app->cache->set($key, $accessToken, 7000);
    }

    public static function getIsvCorpAccessToken($key)
    {
        return Yii::$app->cache->get($key);
    }

    public static function setTmpAuthCode($tmpAuthCode){
        Yii::$app->cache->set("tmp_auth_code", $tmpAuthCode);
    }

    public static function getTmpAuthCode(){
        return Yii::$app->cache->get("tmp_auth_code");
    }

    public static function setPermanentCode($key,$value){
        Yii::$app->cache->set($key, $value);
    }

    public static function getPermanentCode($key){
        return Yii::$app->cache->get($key);
    }

    public static function setActiveStatus($corpKey){
        Yii::$app->cache->set($corpKey,100);
    }

    public static function getActiveStatus($key){
        return Yii::$app->cache->get($key);
    }

    public static function setCorpInfo($data){
        Yii::$app->cache->set('dingding_corp_info',$data);
    }

    public static function getCorpInfo(){
        $corpInfo =  Yii::$app->cache->get('dingding_corp_info');
        return $corpInfo;
    }


    public static function setAuthInfo($key,$authInfo){
        Yii::$app->cache->set($key,$authInfo);
    }

    public static function getAuthInfo($key){
        return Yii::$app->cache->get($key);
    }

    public static function removeByKeyArr($arr){
        foreach($arr as $a){
            Yii::$app->cache->set($a,'');
        }
    }

    public static function get($key)
    {
        return Yii::$app->cache->get($key);
    }
    
    public static function set($key, $value)
    {
        Yii::$app->cache->set($key, $value);
    }
}