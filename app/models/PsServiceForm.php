<?php

namespace app\models;

use Yii;
use yii\base\Model;

class PsServiceForm extends Model
{
    public $img_url;
    public $link_url;
    public $intro;
    public $name;
    public $type;
    public $order_sort;
    public $parent_id;
    public $service_id;
    public $status;
    //跳转类型
    public $header_type;
    //服务效果
    public $service_extend;

    public function rules()
    {
        return [
            [['img_url', 'name', 'type', 'header_type', 'parent_id', 'status'], 'required', 'message' => '{attribute}不能为空!', 'on' => 'create'],
            ['img_url', 'string', 'max' => '255', 'on' => 'create'],
            ['link_url', 'string', 'max' => '255', 'on' => 'create'],
            ['type', 'number', 'on' => 'create'],
            ['name', 'string', 'max' => '20', 'on' => 'create'],
            ['intro', 'string', 'max' => '100', 'on' => 'create'],  
            //['order_sort', 'match', 'pattern' => '/(^[1-9][0-9]$)|(^100$)|(^[1-9]$)$/', 'message' => '{attribute}只能是正整数1-100以内', 'on' => 'create'],
            ['parent_id', 'integer', 'on' => 'create'],
            ['service_id', 'integer', 'on' => ['create', 'check', 'show']], 
            ['status', 'number', 'on' => ['create', 'check', 'parent']], 
        ];
    }

    public function attributeLabels()
    {
        return [
            'name'           => '服务名称',
            'parent_id'      => '父级ID',
            'type'           => '所属业务',
            'header_type'    => '服务类型',
            'service_extend' => '服务效果',
            'intro'          => '服务说明',
            'order_sort'     => '排序',
            'status'         => '状态',
            'img_url'        => '服务图标',
            'link_url'       => '服务链接'
        ];
    }

}
