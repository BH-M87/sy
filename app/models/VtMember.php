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
            [['mobile', 'verify_code', 'member_id'], 'required', 'message'=>'{attribute}不能为空!', 'on' => ['add', 'edit']],
            ['mobile', 'match', 'pattern' => Regular::phone(), 'message' => '{attribute}格式出错', 'on' => ['add', 'edit']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员ID',
            'mobile' => '手机号',
            'verify_code' => '选手ID',
            'update_at' => '修改时间',
            'create_at' => '新增时间',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $p)
    {
        if ($scenario == 'edit') {
            unset($p['member_id']);
            $p['update_at'] = time();
            self::updateAll($p, ['mobile' => $p['mobile']]);
            return true;
        }
        return $this->save();
    }
}
