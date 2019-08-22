<?php
// 社区指南、邻里公约
namespace service\small;

use service\BaseService;

use app\models\PsGuide;
use app\models\PsCommunityConvention;

use service\property_basic\GuideService as proGuideService;

class GuideService extends BaseService 
{
    // 社区指南类型
    public function getType()
    {
        return proGuideService::service()->types;
    }

    // 社区指南类型列表
    public function listWap($param)
    {
        $model = new PsGuide(['scenario' => 'list']);
        if ($model->load($param, '') && $model->validate()) {
            $param['page'] = !empty($param['page'])?$param['page']:1;
            $param['rows'] = !empty($param['rows'])?$param['rows']:10;
            $result = $model->getList($param);
            $data = [];
            if (!empty($result['data'])) {
                foreach ($result['data'] as $key => $value) {
                    $element = [];
                    $element['title'] = $value['title'];
                    $element['address'] = $value['address'];
                    $element['phone'] = $value['phone'];
                    $element['img_url'] = $value['img_url'];
                    $element['hours_start'] = $value['hours_start']>=10?$value['hours_start'].":00":"0".$value['hours_start'].":00";
                    $element['hours_end'] = $value['hours_end']>=10?$value['hours_end'].":00":"0".$value['hours_end'].":00";
                    $data[] = $element;
                }
            }
            return [
                'list' => $data,
                'totals' => $result['count']
            ];
        }
        $errors = array_values($model->getErrors());
        if(!empty($errors[0][0])) {
            return $this->failed($errors[0][0]);
        }
    }

    // 社区公约详情 公约详情接口
    public function conventionDetail($params)
    {
        $model = new PsCommunityConvention(['scenario' => 'detail']);
        if ($model->load($params, '') && $model->validate()) {
            $result = $model->detail($params);
            $result['update_at'] = !empty($result['update_at'])?date('Y.m.d',$result['update_at']):'';
            return ['data'=>$result];
        }
        $errors = array_values($model->getErrors());
        if(!empty($errors[0][0])) {
            return $this->failed($errors[0][0]);
        }
    }
}