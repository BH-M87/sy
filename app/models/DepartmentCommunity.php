<?php

namespace app\models;

use Yii;

class DepartmentCommunity extends BaseModel
{
    public static function tableName()
    {
        return 'department_community';
    }

    public function rules()
    {
        return [];
    }

    public function attributeLabels()
    {
        return [];
    }

    // 小区对应的所有部门id 数组返回
    public static function getCode($community_id)
    {
        $xq_orgcode = PsCommunityModel::findOne($community_id)->event_community_no;
        
        $org_code = DepartmentCommunity::find()
            ->select('jd_org_code, sq_org_code, ga_org_code, xf_org_code, cg_org_code')
            ->where(['xq_orgcode' => $xq_orgcode])->asArray()->one();
            
        return array_values($org_code);
    }
}
