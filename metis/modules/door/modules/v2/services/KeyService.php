<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/7/26
 * Time: 11:14
 */
namespace alisa\modules\door\modules\v2\services;

use common\libs\Curl;
use common\libs\F;
class KeyService extends BaseService
{
    public function getScanCode($params)
    {
        return $this->apiPost('key/get-scan-code',$params, false, false);
    }
}