<?php
/**
 * 主页相关接口
 * User: ZQ
 * Date: 2019-8-29
 * Time: 11:21
 */
namespace service\door;

use common\core\PsCommon;
use service\BaseService;
use service\qiniu\UploadService;

class HomeService extends BaseService
{
    public function getUserId($params)
    {
        $res =  AppUserService::saveAppUser($params);
        if($res){
            return $this->success($res);
        }else{
            return $this->failed('用户保存失败');
        }
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
        $res = UploadService::service()->stream_image($img);
        if ($res['code']) {
            $img_url = $res['data']['filepath'];//七牛的图片地址
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


}