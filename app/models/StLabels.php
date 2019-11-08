<?php
namespace app\models;

use Yii;

use common\core\PsCommon;

class StLabels extends BaseModel
{     
    public static function tableName()
    {
        return 'st_labels';
    }

    public function rules()
    {
        return [
            [['organization_type','organization_id', 'name', 'label_attribute', 'label_type'], 'required', 'on' => ['add', 'edit']],
            [['label_type', 'organization_type', 'created_at', 'updated_at', 'id'], 'integer'],
            [['name'], 'string', 'max' => 15],
            ['label_attribute', 'in', 'range' => [1,2,3]],
            ['label_type', 'in', 'range' => [1,2,3]],
            [['content'], 'string', 'max' => 100],
            ['id','required', 'on' => ['edit', 'delete','show']],
            [['is_sys', 'is_delete'], 'default', 'value' => 1, 'on' => 'add'],
            ['created_at', 'default', 'value' => time(), 'on' => 'add'],
            ['updated_at', 'default', 'value' => time(), 'on' => ['add', 'edit']],
            [['id','organization_id', 'name', 'label_attribute', 'label_type'], 'existData', 'on' => ['add', 'edit']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'organization_type' => '组织类型',
            'organization_id' => '组织id',
            'name' => '标签名称',
            'label_attribute' => '标签属性',
            'label_type' => '标签分类',
            'content' => '标签描述',
            'is_sys' => '是否内置',
            'is_delete' => '是否删除',
            'created_at' => '新曾时间',
            'updated_at' => '修改时间',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $param)
    {
        if ($scenario == 'edit') {
            $param['updated_at'] = time();
            return self::updateAll($param, ['id' => $param['id']]);
        }
        return $this->save();
    }

    // 判断是否已存在
    public function existData()
    {  
        $model = self::find()->where(['organization_id' => $this->organization_id])
            ->orWhere(['is_sys' => 2])
            ->andFilterWhere(['!=', 'id', $this->id])
            ->andWhere(['=', 'name', $this->name])
            ->andWhere(['=', 'label_attribute', $this->label_attribute])
            ->andWhere(['=', 'label_type', $this->label_type])
            ->andWhere(['is_delete' => 1])
            ->asArray()->exists();

        if (!empty($model)) {
            $this->addError('', "数据已存在");
        }
    }

    // 获取单条
    public static function getOne($param)
    {
        return self::find()->where(['id' => $param['id'], 'is_delete' => 1])->asArray()->one();
    }

    // 根据条件获取列表
    public static function getList($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $rows = !empty($param['rows']) ? $param['rows'] : 10;

        return self::find()->select('id, name, label_attribute, label_type, content, is_sys')
            ->where(['organization_id' => $param['organization_id']])
            ->orWhere(['is_sys' => 2])
            ->andFilterWhere(['like', 'name', PsCommon::get($param, 'name')])
            ->andFilterWhere(['=', 'label_attribute', PsCommon::get($param, 'label_attribute')])
            ->andFilterWhere(['=', 'label_type', PsCommon::get($param, 'label_type')])
            ->andWhere(['is_delete' => 1])
            ->orderBy('id desc')
            ->offset(($page - 1) * $rows)->limit($rows)
            ->asArray()->all();
    }
    
    // 根据条件获取总数
    public static function getTotals($param)
    {
        return self::find()
            ->where(['organization_id' => $param['organization_id']])
            ->orWhere(['is_sys' => 2])
            ->andFilterWhere(['like', 'name', PsCommon::get($param, 'name')])
            ->andFilterWhere(['=', 'label_attribute', PsCommon::get($param, 'label_attribute')])
            ->andFilterWhere(['=', 'label_type', PsCommon::get($param, 'label_type')])
            ->andWhere(['is_delete' => 1])
            ->count();
    }

    public static function getDropDown($param)
    {
        if (!$param['street_code']) {
            return self::find()->select('id, name')
                ->where(['is_sys' => 2])
                ->andFilterWhere(['=', 'label_attribute', PsCommon::get($param, 'label_attribute')])
                ->andWhere(['is_delete' => 1])
                ->orderBy('id desc')
                ->asArray()->all();
        } else {
            return self::find()->select('id, name')
                ->where(['organization_id' => $param['street_code']])
                ->orWhere(['is_sys' => 2])
                ->andFilterWhere(['=', 'label_attribute', PsCommon::get($param, 'label_attribute')])
                ->andWhere(['is_delete' => 1])
                ->orderBy('id desc')
                ->asArray()->all();
        }

    }

    // 标签属性
    public static function attribute($index = 0)
    {
        $arr = ['1' => '房屋标签', '2' => '住户标签', '3' => '车辆标签'];
        
        if (!empty($index)) {
            return $arr[$index];
        }

        return $arr;
    }

    // 标签分类
    public static function type($index = 0, $type = 0)
    {
        switch ($type) {
            case '1':
            case '3':
                $arr = ['1' => '日常画像', '2' => '重点关注'];
                break;
            default:
                $arr = ['1' => '日常画像', '2' => '重点关注', '3' => '关怀对象'];
                break;
        }
        
        
        if (!empty($index)) {
            return $arr[$index];
        }
        
        return $arr;
    }
}
