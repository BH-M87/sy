<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_template_bill".
 *
 * @property integer $id
 * @property integer $community_id
 * @property string $name
 * @property integer $type
 * @property integer $paper_size
 * @property integer $layout
 * @property integer $num
 * @property string $note
 * @property integer $create_at
 * @property integer $update_at
 */
class PsTemplateBill extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_template_bill';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'name', 'type', 'num'], 'required'],
            [['community_id', 'type', 'paper_size', 'layout', 'num'], 'integer'],
            [['name'], 'string', 'max' => 15, 'on' => ['add', 'edit']],
            [['note'], 'string', 'max' => 100, 'on' => ['add', 'edit']],
            ['num', 'in', 'range' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], 'message' => '{attribute}必须是1到10的整数', 'on' => ['add', 'edit']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'community_id' => '小区ID',
            'name' => '模板名称',
            'type' => '模板类型',
            'paper_size' => '纸张大小',
            'layout' => '打印布局',
            'num' => '内容数量',
            'note' => '备注',
            'create_at' => '新增时间',
            'update_at' => '更新时间',
        ];
    }

    // 新增 编辑
    public function saveData($scenario, $param)
    {
        if ($scenario == 'edit') {
            $param['update_at'] = time();
            return self::updateAll($param, ['id' => $param['id']]);
        }
        return $this->save();
    }

    // 获取单条
    public static function getOne($param)
    {
        return self::find()->where('id = :id', [':id' => $param['id']])->asArray()->one();
    }

    // 删除
    public static function deleteOne($param)
    {
        return self::deleteAll('id = :id', [':id' => $param['id']]);
    }

    // 根据条件获取列表
    public static function getList($param)
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $rows = !empty($param['rows']) ? $param['rows'] : 10;

        $model = self::find()->orderBy(['id' => SORT_DESC]);

        if (!empty($param['type']) && $param['type'] > 0) {
            $model->andWhere(['=', 'type', $param['type']]);
        }

        if (!empty($param['community_id'])) {
            $model->andWhere(['=', 'community_id', $param['community_id']]);
        }

        if (!empty($param['name'])) {
            $model->andWhere(['like', 'name', $param['name']]);
        }

        $offset = ($page - 1) * $rows;

        return $model->offset($offset)->limit($rows)->asArray()->all();
    }

    // 根据条件获取总数
    public static function getTotals($param)
    {
        $model = self::find();

        if (!empty($param['type']) && $param['type'] > 0) {
            $model->andWhere(['=', 'type', $param['type']]);
        }

        if (!empty($param['community_id'])) {
            $model->andWhere(['=', 'community_id', $param['community_id']]);
        }

        if (!empty($param['name'])) {
            $model->andWhere(['like', 'name', $param['name']]);
        }

        return $model->count();
    }

    // 根据条件获取列表
    public static function getDropDown($param)
    {
        return self::find()->select('id, name')
            ->where(['community_id' => $param['community_id'], 'type' => $param['type']])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()->all();
    }

    // 模板类型
    public static function getType($index = 0)
    {
        $model = ['1' => '通知单模板', '2' => '收据模板'];

        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    // 纸张大小
    public static function getPaperSize($index = 0)
    {
        $model = ['1' => 'A4'];

        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }

    // 打印布局
    public static function getLayout($index = 0)
    {
        $model = ['1' => '纵向'];

        if ($index) {
            return $model[$index];
        } else {
            return $model;
        }
    }
}
