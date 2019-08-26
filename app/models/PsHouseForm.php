<?php

namespace app\models;

use yii\base\Model;

class PsHouseForm extends Model
{
    public $building;
    public $charge_area;
    public $community_id;
    public $group;
    public $intro;
    public $property_type;
    public $room;
    public $status;
    public $unit;
    public $pro_company_id;
    public $page;
    public $rows;
    public $out_room_id;
    public $room_id;
    public $floor_coe;
    public $floor_shared_id;
    public $lift_shared_id;
    public $is_elevator;

    public function rules()
    {
        return [
            [['building', 'charge_area', 'property_type', 'room', 'unit', 'status'], 'required', 
                'message' => '{attribute}不能为空!', 'on' => ['create', 'import']],
            [['community_id'], 'required', 'message' => '还没给您关联小区哦，请联系管理员', 'on' => ['create']],
//            [['building', 'unit', 'room'], 'match', 'pattern' => '/^[A-Za-z0-9]+$/',
//                'message' => '{attribute}只能是英文和数字', 'on' => ['create', 'import']],
            ['group', 'string', 'max' => '15', 'on' => ['create', 'import']], 
            ['building', 'string', 'max' => '20', 'on' => ['create', 'import']],
            ['unit', 'string', 'max' => '20', 'on' => ['create', 'import']],
            ['room', 'string', 'max' => '20', 'on' => ['create', 'import']],
            [['status'], 'in', 'range' => [1, 2], 'on' => ['create',"import"], 'message' => '{attribute}只能是1或2'],
            [['property_type'], 'in', 'range' => [1, 2,3], 'on' => ['create',"import"], 'message' => '{attribute}只能是1或2或3'],
            ['charge_area', 'number', 'on' => ['create', 'import']], 
            ['floor', 'string', 'max' => '200', 'on' => ['create', 'import']],
            ['property_type', 'number', 'on' => ['create']], 

            ['status', 'number', 'on' => ['create']],
//            ['property_type', 'match', 'pattern' => '/(^住宅$)|(^商用$)/', 'on' => ['import'], 'message' => '{attribute}必须是住宅或者商用'],
//            ['status', 'number', 'on' => ['create']],
            ['community_id', 'integer', 'on' => ['create', 'import']],
            
            ['community_id', 'required', 'message' => '{attribute}不能为空!', 'on' => ['list']],

            ['pro_company_id', 'integer', 'on' => ['own']],
            ['pro_company_id', 'required', 'message' => '{attribute}不能为空!', 'on' => 'own'],
            [['page', 'rows'], 'number', 'on' => ['own']], 

            ['out_room_id', 'string', 'max' => '64', 'on' => ['show', 'delete']], 
            ['out_room_id', 'required', 'message' => '{attribute}不能为空!', 'on' => ['show', 'delete']],

            ['room_id', 'string', 'max' => '64', 'on' => ['room_show']],
            ['room_id', 'required', 'message' => '{attribute}不能为空!', 'on' => ['room_show']],


            ['community_id', 'required', 'message' => '{attribute}不能为空!', 'on' => ['get-group','get-building','get-unit','get-room']],
            ['group', 'required', 'message' => '{attribute}不能为空!', 'on' =>  ['get-building','get-unit','get-room']],
            ['building', 'required', 'message' => '{attribute}不能为空!', 'on' => ['get-unit','get-room']],
            ['unit', 'required', 'message' => '{attribute}不能为空!', 'on' =>['get-room']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'group'          => '苑/期/区',
            'building'       => '幢',
            'charge_area'    => '收费面积',
            'community_id'   => '小区ID',
            'property_type'  => '物业类型',
            'room'           => '房号',
            'unit'           => '单元号',
            'status'         => '房屋状态',
            'pro_company_id' => '物业ID',
            'page'           => '页码',
            'rows'           => '每页记录数',
            'out_room_id'    => '房屋编号',
            'room_id'    => '房屋id',
        ];
    }

}
