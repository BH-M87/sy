<?php
namespace app\models;

class PsInspectPoint extends BaseModel
{
    public static function tableName()
    {
        return 'ps_inspect_point';
    }

    public function rules()
    {
        return [
            [['createAt'], 'integer'],
            [['lon', 'lat'], 'number'],
            [['communityId', 'name', 'id'], 'required', 'message' => '{attribute}不能为空!'],
            [['name', 'location'], 'string', 'max' => 50, 'tooLong' => '{attribute}长度不能超过50个字'],
            [['codeImg'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => '巡检点ID',
            'communityId' => '小区Id',
            'name' => '巡检点名称',
            'address' => '巡检点位置',
            'deviceNo' => '设备编号',
            'type' => '打卡方式',
            'lon' => '经度',
            'lat' => '纬度',
            'location' => '地理位置',
            'remark' => '备注',
            'lat' => '纬度',
            'codeImg' => '二维码图片',
            'createAt' => '创建时间',
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        //各个场景的活动属性
        $scenarios['add'] = ['community_id', 'name', 'category_id', 'deviceNo', 'need_location', 'need_photo', 'name'];//新增
        $scenarios['update'] = ['id', 'community_id', 'name', 'category_id', 'deviceNo', 'need_location', 'need_photo'];//编辑
        return $scenarios;
    }
}
