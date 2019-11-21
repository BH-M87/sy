<?php
/**
 * Created by PhpStorm.
 * User: ZQ
 * Date: 2018/10/16
 * Time: 10:32
 */

namespace app\models;

use common\core\Regular;
use yii\base\Model;

class BuildForm extends Model
{
    public $group_id;
    public $building_name;
    public $unit_name;
    public $building_code;
    public $unit_code;
    public $unit;


    public function rules()
    {
        return [
            [['group_id'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['edit']],
            [['building_name', 'unit_name'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['add']],
            [['unit'], 'required', 'message' => '{attribute}不能为空!', 'on' => ['batch-add']],
            [['building_name', 'unit_name'], 'match', 'pattern' => Regular::string(1,20), 'message' => '{attribute}格式出错，填写正确的{attribute}', 'on' => ['add']],
            [['building_code'], 'checkBuildCode'],
            [['unit_code'], 'checkUnitCode'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'group_id' => '苑/期/区ID',
            'building_name' => '楼幢',
            'unit_name' => '单元',
            'building_code' => '楼幢编号',
            'unit_code' => '单元编号',
            'unit' => '楼幢-单元',
        ];
    }

    public function checkBuildCode($attribute)
    {
        $groupCode = $this->$attribute;
        if (!empty($groupCode)) {
            if (!is_numeric($groupCode)) {
                $this->addError($attribute, '编码2位，只可为数字');
            }

        }

    }

    public function checkUnitCode($attribute)
    {
        $groupCode = $this->$attribute;
        if (!empty($groupCode)) {
            if (!is_numeric($groupCode)) {
                $this->addError($attribute, '编码2位，只可为数字');
            }

        }

    }

}