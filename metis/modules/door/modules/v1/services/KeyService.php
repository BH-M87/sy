<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/7/26
 * Time: 11:14
 */
namespace alisa\modules\door\modules\v1\services;

use common\libs\Curl;
use common\libs\F;
class KeyService extends BaseService
{
    public function get_token($params)
    {
        return $this->apiPost('key/get-token',$params, false, false);
    }

    public function check_face($params)
    {
        return $this->apiPost('key/check-face',$params, false, false);
    }

    public function upload_face($data,$img)
    {
        /*图片转换为 base64格式编码*/
        $params['img'] = $this->base64EncodeImage($img);
        $params['type'] = 'face';
        $url = \Yii::$app->params['api_host']."/qiniu/upload/stream-image";
        $res =  Curl::getInstance()->post($url,$params);
        $result = json_decode($res,true);
        if($result['code'] == '20000'){
            $img_url = $result['data']['filepath'];//七牛的图片地址
            //编辑用户
            $params['img'] = $img_url;
            $params['user_id'] = $data['user_id'];
            $params['community_id'] = $data['community_id'];
            $params['room_id'] = $data['room_id'];
            return $this->apiPost('key/upload-face',$params, false, false);
        }else{
           return F::apiFailed($result['error']['errorMsg']);
        }
    }
    /*图片转换为 base64格式编码*/
    public function base64EncodeImage($image_file) {
        $base64_image = '';
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }

    //图片上传，$img 为图片base64编码格式
    public function upload_face_v2($data,$img,$img2 = '')
    {
        /*图片转换为 base64格式编码*/
        $params['img'] = $img;
        $url = \Yii::$app->params['api_host']."/qiniu/upload/stream-image";
        $res =  Curl::getInstance()->post($url,$params);
        $result = json_decode($res,true);
        if($result['code'] == '20000'){
            $img_url = $result['data']['filepath'];//七牛的图片地址
            //编辑用户
            $params['img'] = $img_url;
            $params['member_id'] = $data['member_id'];
            $params['community_id'] = $data['community_id'];
            $params['room_id'] = $data['room_id'];
            $params['base64_img'] = $img2;
            return $this->apiPost('key/upload-face',$params, false, false);
        } else{
            return F::apiFailed($result['error']['errorMsg']);
        }
    }

    //获取房源列表接口
    public function get_house_list($params)
    {
        return $this->apiPost('key/get-house-list',$params, false, false);
    }

    //常用钥匙列表
    public function get_keys($params)
    {
        return $this->apiPost('key/get-keys',$params, false, false);
    }

    //保存访客信息
    public function visitor_add($params)
    {
        $data['app_user_id'] = $params['user_id'];
        $data['community_id'] = $params['community_id'];
        $data['room_id'] = $params['room_id'];
        $data['start_time'] = date("Y-m-d H:i");
        $data['end_time'] = date("Y-m-d H:i",strtotime("+12 hour"));
        $data['type'] = 1;
        $data['code'] = $params['code'];//访客密码
        $url = \Yii::$app->params['api_host']."/webapp/api/add-user-vistor";
        $res =  Curl::getInstance()->post($url,$data);
        $result = json_decode($res,true);
        if($result['code'] == '20000'){
            return F::apiSuccess("保存成功");
        }else{
            return F::apiFailed($result['error']['errorMsg']);
        }
    }

    //获取最后一次访问记录
    public function get_last_visit($params)
    {
        return $this->apiPost('key/get-last-visit',$params, false, false);
    }

    //保存最后一次访问记录
    public function last_visit($params)
    {
        return $this->apiPost('key/last-visit',$params, false, false);
    }


    /**
     * 获取全部钥匙列表
     * @param $params
     * @return array
     */
    public function get_key_list($params)
    {
        return $this->apiPost('key/get-key-list',$params, false, false);
    }

    /**
     * 编辑常用钥匙
     * @param $params
     * @return array
     */
    public function edit_keys($params)
    {
        return $this->apiPost('key/edit-keys',$params, false, false);
    }

    /**
     * 上传图片到七牛接口
     * @param $img
     * @return string
     */
    public function upload_image($img)
    {
        $img_url = [];
        $images = is_array($img) ? $img : explode(',',$img);
        foreach ($images as $key=>$value){
            /*图片转换为 base64格式编码*/
            $params['img'] = $this->base64EncodeImage($value);
            //$params['type'] = 'face';
            $url = \Yii::$app->params['api_host']."/qiniu/upload/stream-image";
            $res =  Curl::getInstance()->post($url,$params);
            $result = json_decode($res,true);
            if($result['code'] == '20000'){
                $img_url[] = $result['data']['filepath'];//七牛的图片地址
            }else{
                $img_url = $result['error']['errorMsg'];
            }
        }

        if($img_url && count($img_url) == count($img)){
            return F::apiSuccess($img_url);
        }else{
            return F::apiFailed("上传失败");
        }

    }

    /**
     * 访客密码
     * @param $params
     * @return array
     */
    public function visitor_password($params)
    {
        return $this->apiPost('key/visitor-password',$params, false, false);
    }

    /**
     * 获取开门二维码
     * @param $params
     * @return array
     */
    public function get_code($params)
    {
        return $this->apiPost('key/get-code',$params, false, false);
    }

    /**
     * 远程开门
     * @param $params
     * @return array
     */
    public function open_door($params)
    {
        return $this->apiPost('key/open-door',$params, false, false);
    }
}