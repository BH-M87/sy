<?php
namespace common\core;

use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use Qiniu\Zone;

class ImageManage{

    const ACCESS_kEY  = "c38ZLfSEfktHZw54O6XCgmPwjg0bQAW1svvFWQ6b";
    const  SECRET_KEY= "DxYXfBO6Q0e4dcuAvRCpNzaNQo_zSbq7-3NwArq4";

	//单例
	private static $instance = null;

	public static function getInstance($options = array()){
		if(empty(self::$instance)){
			self::$instance = new self($options);
		}
		return self::$instance;
	}


    public  function upfile($bucket,$keyName='',$inputName) {
        $auth = new Auth(self::ACCESS_kEY, self::SECRET_KEY);
        $token = $auth->uploadToken($bucket);
        $uploadMgr = new UploadManager();
        list($ret, $err) = $uploadMgr->putFile($token, $keyName, $inputName);
        if ($err !== null) {
           return false;
        } else {
           return $ret['key'];
        }
    }

    public  function moveFile($bucket1,$keyName,$bucket2,$keyName2) {
        $auth = new Auth(self::ACCESS_kEY, self::SECRET_KEY);
        $bucketMgr = new BucketManager($auth);
        $err = $bucketMgr->move($bucket1, $keyName, $bucket2, $keyName2);
        return $err;
    }

    public function copyFile($bucket1,$keyName,$bucket2,$keyName2) {
        $auth = new Auth(self::ACCESS_kEY, self::SECRET_KEY);
        $bucketMgr = new BucketManager($auth);
        $err = $bucketMgr->copy($bucket1, $keyName, $bucket2, $keyName2);
        return $err;
    }

    public function getFile($bucket,$key){
        echo "<pre>";
        $auth = new Auth(self::ACCESS_kEY, self::SECRET_KEY);
        $bucketMgr = new BucketManager($auth);
        //获取文件的状态信息
        list($ret, $err) = $bucketMgr->stat($bucket, $key);
        print_r($ret);
        if ($err !== null) {
            return false;
        } else {
            return true;
        }
   }
    public function getBucketDomain($bucket){
        echo "<pre>";
        $auth = new Auth(self::ACCESS_kEY, self::SECRET_KEY);
        $bucketMgr = new BucketManager($auth);
        //获取文件的状态信息
        $result = $bucketMgr->apiGet($bucket);
        print_r($result);
    }

	private function __clone(){
		//防止clone函数克隆对象，破坏单例模式
	}
	
	/**
	**	析构函数
	**
	**/
	public function __destruct(){
		
	}
	
}
