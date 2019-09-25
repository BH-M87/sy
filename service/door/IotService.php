<?php
/**
 * iot 相关接口服务 获取开门密码（访客密码、公共密码、住户密码）、远程开门、开门二维码获取
 * User: wenchao.feng
 * Date: 2018/10/11
 * Time: 18:04
 */
namespace service\door;


use service\BaseService;

class IotService extends BaseService
{
    /**
     * 获取密码（住户密码及访客密码）
     * @param $data
     * @return array
     */
    public function getPassword($data,$device_name='')
    {
        $url = '/rest/door/addCommunityPassword';
        $params['userId'] = $data['member_id'];

        //根据房屋，住户查询相关信息
        $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
        $params['communityNo'] = $unitInfo['community_no'];
        $params['buildingNo'] = $unitInfo['unit_no'];
        $params['roomNo'] = $unitInfo['out_room_id'];
        $params['password'] = rand(100000,999999);

        if ($data['pwd_type'] == 1) {
            //访客密码
            $params['validityTime'] = time() + 12*3600;
            $params['useTime'] = 2;
            $params['type'] = 3;
        } elseif ($data['pwd_type'] == 2) {
            //住户密码
            $params['validityTime'] = time() + 30*24*3600;
            $params['type'] = 2;
        }
        $res = $this->curlExec($url, $params);
        if ($res['code']) {
            //密码生成成功
            if ($data['pwd_type'] == 1 && $data['visitor_type'] != 1) {
                $re = VisitorService::service()->saveRoomVistor(array_merge($data, $params,$unitInfo));
            } elseif ($data['pwd_type'] == 2 && $data['visitor_type'] != 1) {
                $data['code_img'] = "";
                $re = VisitorService::service()->saveMemberCode(array_merge($data, $params,$unitInfo));
            }

            if ($re || $data['visitor_type'] == 1) {
                $reData['device_name'] = $device_name;
                $reData['password'] = $params['password'];
                $reData['expired_time'] = date("Y-m-d H:i:s", $params['validityTime']);
                $reData['community_name'] = $unitInfo['community_name'];
                $reData['group'] = $unitInfo['group'];
                $reData['building'] = $unitInfo['building'];
                $reData['unit'] = $unitInfo['unit'];
                $reData['room'] = $unitInfo['room'];
                $reData['use_time'] = $params['useTime'] ? $params['useTime'] : '';
                return $this->success($reData);
            } else {
                return $this->failed("访客密码保存失败");
            }
        } else {
            return $this->failed($res['message']);
        }
    }

    public function syncPassword($data, $password)
    {
        $url = '/rest/door/addCommunityPassword';
        $params['userId'] = $data['member_id'];

        //根据房屋，住户查询相关信息
        $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
        $params['communityNo'] = $unitInfo['community_no'];
        $params['buildingNo'] = $unitInfo['unit_no'];
        $params['roomNo'] = $unitInfo['out_room_id'];
        $params['password'] = $password;

        if ($data['pwd_type'] == 1) {
            //访客密码
            $params['validityTime'] = time() + 12*3600;
            $params['useTime'] = 2;
            $params['type'] = 3;
        } elseif ($data['pwd_type'] == 2) {
            //住户密码
            $params['validityTime'] = time() + 30*24*3600;
            $params['type'] = 2;
        }
        $res = $this->curlExec($url, $params);
        if ($res['code'] == 1) {
            $reData['password'] = $params['password'];
            return $this->success($reData);
        } else {
            return $this->failed("访客密码获取失败");
        }
    }

    /**
     * 获取开门二维码
     * @param $data
     * @return array
     */
    public function getOpenCode($data)
    {
        $url = '/rest/door/generateQRCode';
        $params['userId'] = $data['member_id'];
        //根据房屋，住户查询相关信息
        $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
        $params['communityNo'] = $unitInfo['community_no'];
        $params['buildingNo'] = $unitInfo['unit_no'];
        $params['roomNo'] = $unitInfo['out_room_id'];
        $params['visitTime'] = ''; // 业主不需要传到访时间 访客才需要
        $params['exceedTime'] = time() + 60;
        $userType = RoomService::service()->findRoomUserById($data['room_id'],$data['member_id']);;
        $params['userType'] = $userType;
        // {"userId":"452","communityNo":"AKAY9HBBC3301","buildingNo":"20181029000044ghNtHS","roomNo":"20181029140338000044gyabl4","visitTime":1543987776,"exceedTime":1546579776}
        $res = $this->curlExec($url, $params);
        if ($res['code']) {
            $data['code_img'] = time(); // 默认个时间 api那边会生成二维码并更新 因为更新的时候没有变化 update的结果会返回0
            $data['password'] = '';
            $data['validityTime'] = $params['exceedTime'];
            $userCodeData = array_merge($data, $params, $unitInfo);
            $re = VisitorService::service()->saveMemberCode($userCodeData);
            if ($re) {
                $reData['code_img'] = $res['data'];
                $reData['expired_time'] = date("Y-m-d H:i:s", $params['exceedTime']);
                $reData['community_name'] = $unitInfo['community_name'];
                $reData['group'] = $unitInfo['group'];
                $reData['building'] = $unitInfo['building'];
                $reData['unit'] = $unitInfo['unit'];
                $reData['room'] = $unitInfo['room'];
                return $this->success($reData);
            } else {
                return $this->failed("二维码保存失败！");
            }
        } else {
            return $this->failed("二维码获取失败！");
        }
    }

    public function getVisitorOpenCode($data)
    {
        $url = '/rest/door/generateQRCode';
        $params['userId'] = $data['member_id'];
        $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
        $params['communityNo'] = $unitInfo['community_no'];
        $params['buildingNo'] = $unitInfo['unit_no'];
        $params['roomNo'] = $unitInfo['out_room_id'];
        $params['visitorId'] = $data['visitor_id'];
        $params['userType'] = RoomService::service()->findRoomUserById($data['room_id'],$data['member_id']);
        if (empty($data['is_owner'])) {
            // 有值代表是业主邀请自己 访客才有到访时间 因为java是用到访时间判断是不是访客的 业主不能当访客 不然业主二维码身份会被更改会访客
            $params['visitTime'] = time();// 业主不需要传到访时间 访客才需要
        }
        $params['exceedTime'] = time() + 60;
        $res = $this->curlExec($url, $params);
        if ($res['code']) {
            $reData['code_img'] = $res['data'];
            $reData['expired_time'] = date("Y-m-d H:i:s", $params['exceedTime']);
            $reData['community_name'] = $unitInfo['community_name'];
            $reData['group'] = $unitInfo['group'];
            $reData['building'] = $unitInfo['building'];
            $reData['unit'] = $unitInfo['unit'];
            $reData['room'] = $unitInfo['room'];
            return $this->success($reData);
        } else {
            return $this->failed("二维码获取失败！");
        }
    }


    /**
     * 远程开门
     * @param $data
     * @return bool
     */
    public function openDoor($data)
    {
        $url = '/rest/door/remoteOpenDoor';
        $params['userId'] = $data['member_id'];
        $params['deviceNo'] = $data['device_no'];
        $params['roomNo'] = $data['room_no'];
        $params['userType'] = $data['user_type'];

        $res = $this->curlExec($url, $params);
        if ($res['code'] == 1 && $res['data']) {
            return true;
        } else {
            return "设备当前为离线状态";
        }
    }

    private function curlExec($url, $params)
    {
        $url = \Yii::$app->params['iot_ip'].$url;
        $params['appKey'] = "DNAKE";
        $params['timestamp'] = time();
        $secret = "ea768bf4f6cc7fadc79c86de55a65ef5";
        ksort($params);
        $paramStr = '';
        foreach ($params as $key => $val) {
            if ($val !== '') {
                $paramStr .= $key.'='.$val.'&';
            }
        }
        $paramStr = substr($paramStr, 0, -1) . $secret;
        $params['sign'] = md5($paramStr);
        $options = [
            'CURLOPT_HTTPHEADER' => [
                'Content-Type:application/json',
            ]
        ];
        $curlObject = new Curl($options);
        //var_dump($url);var_dump(json_encode($params));die;
        $res = $curlObject->post($url, json_encode($params));
        return json_decode($res, true);
    }

     // 访客 新增
    public function visitorAdd($data)
    {
        // 根据房屋，住户查询相关信息
        $unitInfo = VisitorOpenService::service()->getUnitByRoomId($data);
        // 密码
        $params['userId'] = $data['member_id'];
        $params['communityNo'] = $unitInfo['community_no'];
        $params['buildingNo'] = $unitInfo['unit_no'];
        $params['roomNo'] = $unitInfo['out_room_id'];
        $params['password'] = rand(100000, 999999);
        $params['validityTime'] = !empty($data['end_time']) ? $data['end_time'] : time() + 24*3600;
        $params['useTime'] = 2;
        $params['type'] = 3;

        $params['car_number'] = !empty($data['car_number']) ? $data['car_number'] : '';
        $params['sex'] = !empty($data['sex']) ? $data['sex'] : '1';
        $res = $this->curlExec('/rest/door/addCommunityPassword', $params);

        if ($res['code']) {
            // 密码 生成成功
            $re = VisitorService::service()->saveRoomVistor(array_merge($data, $params, $unitInfo));

            // 二维码
            $paramsQr['userId'] = $data['member_id']; // 用户id
            $paramsQr['communityNo'] = $unitInfo['community_no']; // 小区编号
            $paramsQr['buildingNo'] = $unitInfo['unit_no']; // 楼幢编号
            $paramsQr['roomNo'] = $unitInfo['out_room_id']; // 房间号
            $paramsQr['visitorId'] = $re; // 访客表记录id 后面开门记录的时候查询信息用
            $paramsQr['userType'] = RoomService::service()->findRoomUserById($data['room_id'],$data['member_id']);
            if (empty($data['is_owner'])) { // 有值代表是业主邀请自己 访客才有到访时间 因为java是用到访时间判断是不是访客的 业主不能当访客 不然业主二维码身份会被更改会访客
                $paramsQr['visitTime'] = !empty($data['start_time']) ? $data['start_time'] : time(); // 到访时间
                $paramsQr['visitTime'] = "".$paramsQr['visitTime'];
            }
            
            $paramsQr['exceedTime'] = !empty($data['end_time']) ? $data['end_time'] : 24*3600; // 结束时间
            $paramsQr['exceedTime'] = "".$paramsQr['exceedTime'];
            $resQr = $this->curlExec('/rest/door/generateQRCode', $paramsQr);

            if ($re) {
                $reData['id'] = $re;
                $reData['qrcode'] = $resQr['data']; // 返回报文 api去生成二维码
                return $this->success($reData);
            } else {
                return $this->failed("访客邀请失败");
            }
        } else {
            return $this->failed("访客邀请失败");
        }
    }
}