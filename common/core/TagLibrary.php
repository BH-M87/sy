<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/20
 * Time: 11:34
 */

namespace common\core;


class TagLibrary
{
    /**
     * @param $type
     * @return mixed
     * @api 查询用户相关的标签信息
     * @author wyf
     * @date 2019/8/20
     */
    public static function roomUser($type)
    {
        $identity_type = [
            '1' => '业主',
            '2' => '家人',
            '3' => '租客',
        ];
        $identity_status = ['1' => '未认证', '2' => '已认证', '3' => '迁出', '4' => '迁出'];

        return $$type ?? "";
    }
}
