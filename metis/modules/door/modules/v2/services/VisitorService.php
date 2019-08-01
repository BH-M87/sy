<?php
/**
 * Created by PhpStorm.
 * User: wyf
 * Date: 2019/5/31
 * Time: 13:41
 */

namespace alisa\modules\door\modules\v2\services;


class VisitorService extends BaseService
{
    public function visitorIndex($params)
    {
        return $this->apiPost('visitor/visitor-index', $params, false, false);
    }
}