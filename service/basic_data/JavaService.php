<?php
/**
 * Created by PhpStorm.
 * User: zhangqiang
 * Date: 2019-06-04
 * Time: 16:49
 */

namespace service\basic_data;


use common\core\JavaCurl;

class JavaService extends BaseService
{

   //测试java接口
    public function test($data)
    {
        $nameList['route'] = "/community/list";
        $nameList['token'] = $data['token'];

        return JavaCurl::getInstance()->pullHandler($nameList);
    }
}