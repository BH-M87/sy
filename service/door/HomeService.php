<?php
/**
 * 主页相关接口
 * User: ZQ
 * Date: 2019-8-29
 * Time: 11:21
 */
namespace service\door;

use app\models\PsAppUser;
use app\models\PsMember;
use app\models\PsResidentAudit;
use app\models\PsRoomUser;
use common\core\PsCommon;
use common\MyException;
use service\BaseService;
use service\qiniu\UploadService;
use service\resident\ResidentService;

class HomeService extends BaseService
{
    public function getUserId($params, $systemType = '')
    {
        $result =  AppUserService::saveAppUser($params);
        if ($result === false) {
            return $this->failed("用户保存失败");
        } else {
            //支付宝实名认证的用户直接是认证了
            if($result['is_certified'] == "1" || $params['is_certified']=='T'){
                if (!empty($params['mobile']) && in_array($systemType, ['edoor', 'fczl'])) {
                    //业主认证需要的参数
                    $responseData['app_user_id'] = $result['id'];
                    $responseData['mobile'] = $params['mobile'];
                    $responseData['user_name'] = $params['user_name'];
                    $responseData['sex'] = $result['sex'];
                    //自动走一遍业主认证
                    MemberService::service()->authTo($responseData);
                }
            }
            return $this->success(['user_id' => $result['id']]);
        }
    }

    public function saveUserId($params)
    {
        $params['user_id'] = PsAppUser::find()->select(['channel_user_id'])->where(['id'=>$params['user_id']])->asArray()->scalar();
        AppUserService::saveAppUser($params,2);
    }

    //业主认证
    public function authTo($params)
    {
        $res =  MemberService::service()->authTo($params);
        if($res){
            return $this->success($res['data']);
        }else{
            return $this->failed('用户保存失败');
        }
    }

    //获取首页数据
    public function getIndexData($params)
    {
        $appUserId = PsCommon::get($params, 'app_user_id');
        $communityId = PsCommon::get($params, 'community_id');
        $roomId = PsCommon::get($params, 'room_id');
        $mac = PsCommon::get($params, 'mac');
        return MemberService::service()->doorIndexData($appUserId, $communityId, $roomId,$mac);
    }


    //获取biz_id
    public function get_biz_id($user_id)
    {
        //$user_id  = PsCommon::get($params,'user_id');
        return SelfService::service()->get_biz_id($user_id);
    }

    //图片上传，$img 为图片base64编码格式
    public function upload_face_v2($data,$img,$img2 = '')
    {
        /*图片转换为 base64格式编码*/
        $params['img'] = $img;
        $res = UploadService::service()->stream_image_oss($img);
        if ($res['code']) {
            $img_url = $res['data'];
            //$img_url = $res['data']['filepath'];//七牛的图片地址
            return KeyService::service()->upload_face($data['member_id'],$img_url,$data['room_id'],$img2);
        }else{
            return $res;
        }

    }

    /**
     * 远程开门
     * @param $params
     * @return array
     */
    public function open_door($user_id,$device_no,$supplier_name,$roomId)
    {
        return KeyService::service()->open_door($user_id,$device_no,$supplier_name,$roomId);
    }

    /**
     * @api 取消蒙层指导
     * @return array
     */
    public function userGuide($app_user_id)
    {
        if (empty($app_user_id)) {
            return $this->failed('用户编号不能为空');
        }
        $model = PsAppUser::find()->where(['id' => $app_user_id])->one();
        if ($model) {
            $model->is_guide = 2;
            if ($model->save()) {
                return $this->success();
            } else {
                return $this->failed('操作失败');
            }
        } else {
            return $this->failed('用户不存在');
        }
    }

    /**
     * @api 人脸列表
     * @date 2019/5/31
     * @param $appUserId
     * @param $roomId
     * @return array
     * @throws MyException
     */
    public function getFaceList($appUserId, $roomId)
    {

        $memberId = \service\resident\MemberService::service()->getMemberId($appUserId);
        if (!$memberId) {
            return $this->failed('用户不存在');
        }
        //获取当前房屋信息
        $current = PsRoomUser::find()
            ->alias('room_user')
            ->select('room_user.id,room_user.identity_type, room_user.name, member.face_url,room_user.status')
            ->innerJoin('ps_member member', 'member.id = room_user.member_id')
            ->where(['room_user.room_id' => $roomId, 'room_user.member_id' => $memberId])
            ->andWhere(['!=', 'room_user.status', 1])
            ->orderBy('id desc')
            ->asArray()
            ->one();
        if (!$current) {
            $current = PsResidentAudit::find()
                ->alias('resident')
                ->select('resident.id,resident.identity_type, resident.name, member.face_url,resident.status')
                ->innerJoin('ps_member member', 'member.id = resident.member_id')
                ->where(['resident.room_id' => $roomId, 'resident.member_id' => $memberId])
                ->andWhere(['!=', 'resident.status', 1])
                ->orderBy('id desc')
                ->asArray()
                ->one();
            $current['is_audit'] = 1;
        } else {
            $current['is_audit'] = 2;
        }
        if (!$current) {
            throw new MyException('当前房屋信息不存在');
        }
        //家人和租客，只可以看到自己的头像
        if ($current['identity_type'] != 1) {
            $data = [];
        } else {
            //获取其他家人或者租客信息
            $data = PsRoomUser::find()
                ->alias('room_user')
                ->select('room_user.member_id, room_user.name, room_user.identity_type,member.face_url')
                ->where(['room_user.room_id' => $roomId, 'room_user.status' => [1, 2]])
                ->leftJoin('ps_member member', 'member.id = room_user.member_id')
                ->andWhere(['!=', 'room_user.member_id', $memberId])
                ->orderBy('room_user.identity_type asc, room_user.status asc, room_user.id desc')
                ->asArray()->all();
        }
        $info = self::transFormFaceInfo($memberId, $current, $data);
        return $this->success($info);
    }

    /**
     * @api 数据转换
     * @date 2019/5/22
     * @param $memberId
     * @param $currentInfo
     * @param $restsInfo
     * @return array
     */
    protected static function transFormFaceInfo($memberId, $currentInfo, $restsInfo)
    {
        if ($currentInfo['is_audit'] == 1) {
            $house_status = $currentInfo['status'] == 0 ? 5 : 6;
        }
        $info = [
            'member_id' => (int)$memberId,
            'face_url' => empty($currentInfo['face_url']) ? '' : $currentInfo['face_url'],
            'identity_type' => (int)$currentInfo['identity_type'],
            'name' => $currentInfo['name'],
            'identity_type_label' => ResidentService::service()->identity_type[$currentInfo['identity_type']],
            'house_status' => empty($house_status) ? $currentInfo['status'] : $house_status,
        ];
        if ($restsInfo) {
            foreach ($restsInfo as $item) {
                $data = [
                    'member_id' => (int)$item['member_id'],
                    'face_url' => empty($item['face_url']) ? '' : $item['face_url'],
                    'identity_type' => (int)$item['identity_type'],
                    'name' => $item['name'],
                    'identity_type_label' => ResidentService::service()->identity_type[$item['identity_type']]
                ];
                if ($item['identity_type'] == 1) {
                    $info['list'][] = $data;
                } elseif ($item['identity_type'] == 2) {
                    $info['people_list'][] = $data;
                } elseif ($item['identity_type'] == 3) {
                    $info['rent_list'][] = $data;
                }
            }
        } else {
            $info['list'] = [];
            $info['people_list'] = [];
            $info['rent_list'] = [];
        }
        return $info;
    }



}