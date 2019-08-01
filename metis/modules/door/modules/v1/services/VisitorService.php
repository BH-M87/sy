<?php
namespace alisa\modules\door\modules\v1\services;

use common\libs\Curl;
use common\libs\F;

class VisitorService extends BaseService
{
    public function visitorMsg($params)
    {
        return $this->apiPost('visitor/visitor-msg',$params, false, false);
    }

    public function visitorCancel($params)
    {
        return $this->apiPost('visitor/visitor-cancel',$params, false, false);
    }

    public function visitorList($params)
    {
        return $this->apiPost('visitor/visitor-list',$params, false, false);
    }

    public function visitorDelete($params)
    {
        return $this->apiPost('visitor/visitor-delete',$params, false, false);
    }

    public function visitorAdd($params)
    {
        return $this->apiPost('visitor/visitor-add',$params, false, false);
    }

    public function upload_face($data,$img,$img2 = '')
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
            $params['visitor_id'] = $data['visitor_id'];
            $params['base64_img'] = $img2;
            return $this->apiPost('visitor/upload-face',$params, false, false);
        } else{
            return F::apiFailed($result['error']['errorMsg']);
        }
    }

    public function visitorIndex($params)
    {
        return $this->apiPost('visitor/visitor-index',$params, false, false);
    }

    /**
     * 获取开门二维码
     * @param $params
     * @return array
     */
    public function get_code($params)
    {
        return $this->apiPost('visitor/visitor-qrcode',$params, false, false);
    }
}