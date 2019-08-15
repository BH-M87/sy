<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/15
 * Time: 11:24
 */

namespace service\producer;


use service\BaseService;

class CoreService extends BaseService
{
    /**
     * 获取同步标识，是否把数据同步到数据平台跟公安厅
     * @param $community_id
     * @param $supplier_id
     * @param string $supplier_type
     * $supplier_type 1道闸，2门禁，空全部
     * @return false|null|string
     */
    public function getSyncDatacenter($community_id, $supplier_id, $supplier_type = '')
    {
        //根据供应商类型来区分是查找门禁还是道闸的同步类型 add by zq 2019-5-9
        if (empty($supplier_type)) {
            $supplier_type = [1, 2];
        }
        /*return ParkingSupplierCommunity::find()
            ->select(['sync_datacenter'])
            ->where(['community_id' => $community_id, 'supplier_id' => $supplier_id, 'supplier_type' => $supplier_type])
            ->scalar();*/
    }
}