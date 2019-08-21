<?php
/**
 * User: ZQ
 * Date: 2019/8/21
 * Time: 11:42
 * For: ****
 */

namespace services\resident;


class BaseService extends \service\BaseService
{
    protected $communityId ;
    private $FORMAT_TYPE = ['Y-m-d H:i:s', 'Y-m-d', 'Y/m/d H:i:s', 'Y/m/d', 'Y.m.d', 'Y.m.d H:i:s', 'Y年m月d日'];

    public function __construct($communityId)
    {
        $this->communityId = $communityId;
    }
}