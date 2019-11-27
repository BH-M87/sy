<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/11/27
 * Time: 14:51
 * Desc: java接口调用service
 */
namespace service\property_basic;

use service\BaseService;
use common\core\JavaCurl;

class JavaService extends BaseService{

    /**
     * Notes: 小区下拉列表
     */
    public function communityList($query){
        $query['route'] = '/community/list';
        $result =  JavaCurl::getInstance()->pullHandler($query);
        print_r($result);die;
        $emp[0] = ['key' => "99999",'name' => '全部'];
        $res['list'] = array_merge($emp,$result['list']);
        return $res;
    }
}