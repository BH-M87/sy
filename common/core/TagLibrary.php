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
        $sexes = [
            0 => ['id' => 0, 'name' => '未设置'],
            1 => ['id' => 1, 'name' => '男'],
            2 => ['id' => 2, 'name' => '女'],
        ];
        $change_detail = [
            '1' => '迁入',
            '2' => '迁出',
            '3' => '死亡',
            '4' => '失联',
            '5' => '购房入住',
            '6' => '出生',
            '7' => '其他',
        ];
        $face = [
            '1' => '党员',
            '2' => '团员',
            '3' => '群众',
        ];
        $household_type = [
            '1' => '非农业户口',
            '2' => '农业户口',
        ];
        $identity_type = [
            '1' => '业主',
            '2' => '家人',
            '3' => '租客',
        ];
        $live_detail = [
            '1' => '空巢老人',
            '2' => '独居',
            '3' => '孤寡',
            '4' => '其他',
        ];
        $live_type = [
            '1' => '户在人在',
            '2' => '户在人不在',
            '3' => '常住(已购房，户籍不在)',
            '4' => '承租',
            '5' => '空房',
            '6' => '借住',
            '7' => '其他',
            '8' => '人在户不在',
        ];
        $marry_status = [
            '1' => '已婚',
            '2' => '未婚',
            '3' => '离异',
            '4' => '分居',
            '5' => '丧偶',
        ];
        $identity_status = ['1' => '未认证', '2' => '已认证', '3' => '迁出', '4' => '迁出'];

        return $$type ?? "";
    }
}
