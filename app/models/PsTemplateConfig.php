<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "ps_template_config".
 *
 * @property integer $id
 * @property integer $template_id
 * @property integer $name
 * @property string $field_name
 * @property integer $type
 * @property integer $width
 * @property string $logo_img
 * @property string $note
 * @property integer $create_at
 * @property integer $update_at
 */
class PsTemplateConfig extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_template_config';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['template_id', 'name', 'field_name', 'type'], 'required'],
            [['template_id', 'type', 'width'], 'integer'],
            [['field_name', 'name'], 'string', 'max' => 20, 'on' => ['add']],
            [['logo_img'], 'string', 'max' => 255, 'on' => ['add']],
            [['note'], 'string', 'max' => 100, 'on' => ['add']],
            ['create_at', 'default', 'value' => time(), 'on' => 'add'],
            [['logo_img', 'note'], 'default', 'value' => '', 'on' => 'add'],
            [['width'], 'default', 'value' => 0, 'on' => 'add'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_id' => '模板ID',
            'name' => '显示内容',
            'field_name' => '字段名称',
            'type' => '类型',
            'width' => '宽度',
            'logo_img' => 'Logo图片地址',
            'note' => '说明内容',
            'create_at' => '新增时间',
            'update_at' => '修改时间',
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

    // 删除所有
    public static function deletes($param)
    {
        return self::deleteAll('template_id = :template_id and field_name = :field_name and type = :type', 
            [':template_id' => $param['template_id'], ':field_name' => $param['field_name'], ':type' => $param['type']]);
    }
    
    // 根据条件获取列表
    public static function getList($param, $select, $sort = 'SORT_DESC')
    {
        $page = !empty($param['page']) ? $param['page'] : 1;
        $rows = !empty($param['rows']) ? $param['rows'] : 10;

        $model = self::find()->select($select)->orderBy(['id' => $sort]);

        if (!empty($param['type'])) {
            $model->andWhere(['=', 'type', $param['type']]);
        }

        if (!empty($param['template_id'])) {
            $model->andWhere(['=', 'template_id', $param['template_id']]);
        }

        if (!empty($param['name'])) {
            $model->andWhere(['like', 'name', $param['name']]);
        }

        $offset = ($page-1) * $rows;
        $dataList =  $model->offset($offset)->limit($rows)->asArray()->all();
        if($param['type']==1){
            $temList=[];
            if(!empty($dataList)){
                foreach ($dataList as $data){
                    $tem=$data;
                    if($data['field_name']=='img'){
                        $code_image = PsLifeServices::findOne(['community_id' => $param['community_id']])->code_image;
                        $tem['logo_img'] = !empty($code_image) ? $code_image : '';
                    }
                    $temList[]=$tem;
                }
            }
            return $temList;
        }else{
            return $dataList;
        }
    }
    
    // 根据条件获取总数
    public static function getTotals($param)
    {
        $model = self::find();

        if (!empty($param['type'])) {
            $model->andWhere(['=', 'type', $param['type']]);
        }
            
        if (!empty($param['name'])) {
            $model->andWhere(['like', 'name', $param['name']]);
        }

        return $model->count();
    }
}
