<?php
namespace app\models;

use Yii;
use common\core\Regular;

class VtMember extends BaseModel
{
    public static function tableName()
    {
        return 'vt_member';
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

     // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            $p['update_at'] = time();
            self::updateAll($p, ['id' => $p['id']]);
            return true;
        }
        return $this->save();
    }
}
