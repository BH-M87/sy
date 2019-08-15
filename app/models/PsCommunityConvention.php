<?php
/**
 * Created by PhpStorm.
 * User: zhouph
 * Date: 2019/3/20
 * Time: 15:36
 * Desc: 邻里公约 model
 */
namespace app\models;

class PsCommunityConvention extends BaseModel {
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ps_community_convention';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['community_id', 'title', 'content'], 'required','message' => '{attribute}不能为空！','on'=>'add'],
            [['id','community_id', 'title', 'content'], 'required','message' => '{attribute}不能为空！','on'=>'update'],
            [['community_id'],'required','message'=>'{attribute}不能为空！','on'=>'detail'],
            ['community_id','infoExit','on'=>'detail'],
            [['id','community_id'], 'infoData', 'on' => ['update']],
            [['id','community_id'], 'unique', 'on' => ['add']],
            [['community_id', 'create_at', 'update_at'], 'integer'],
            [['title'], 'string', 'max' => 30],
            [['content'], 'string'],
            [['create_at','update_at'],'default','value' => time(),'on' => 'add'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'community_id'  => '小区ID',
            'title'         => '公约标题',
            'content'       => '公约内容',
            'create_at'     => '创建时间',
            'update_at'     => '更新时间',
        ];
    }

    /***
     * 新增
     * @return true|false
     */
    public function saveData()
    {
        return $this->save();
    }

    /***
     * 修改
     * @return bool
     */
    public function edit($param)
    {
        $param['update_at'] = time();
        return self::updateAll($param, ['id' => $param['id'],'community_id' => $param['community_id']]);
    }

    /***
     * 自定义验证企业客户是否存在
     * @param $attribute
     */
    public function infoData($attribute){
        if(!empty($this->id)){
            $res = static::find()->select(['id'])->where('id=:id and community_id=:community_id',[':id'=>$this->id,':community_id'=>$this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该小区公约不存在！");
            }
        }
    }

    /*
     * 改小区下存在公约
     */
    public function infoExit($attribute){
        if(!empty($this->community_id)){
            $res = static::find()->select(['id'])->where('community_id=:community_id',[':community_id'=>$this->community_id])->asArray()->one();
            if (empty($res)) {
                $this->addError($attribute, "该小区公约不存在！");
            }
        }
    }

    /*
     * 公约详情
     */
    public function detail($param){
        $field = ['id','community_id','title','content','update_at'];
        $model = static::find()->select($field);
        if(!empty($param['community_id'])){
            $model->andWhere(['=','community_id',$param['community_id']]);
        }
        return $model->asArray()->one();
    }
}