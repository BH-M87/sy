<?php
namespace app\models;

use Yii;

class StScheduling extends BaseModel
{
    public static $user_type = [1 => '值班负责人', 2 => '值班人员'];

    public static function tableName()
    {
        return 'st_scheduling';
    }

    public function rules()
    {
        return [
        ];
    }

    public function attributeLabels()
    {
        return [
        ];
    }

    // 获取列表
    public static function getList($p)
    {
        $m = self::find()->alias('A')->leftJoin('user_info B', 'B.id = A.user_id')->select('A.user_id, B.username as user_name, B.mobile_number as user_mobile, A.user_type')->where(['day_type' => $p['day_type']])->asArray()->all();

        foreach ($m as &$v) {
            $v['user_type_desc'] = self::$user_type[$v['user_type']];
        }

        return $m;
    }
}
