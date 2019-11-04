<?php
namespace service\small;

use app\models\Answer;
use app\models\PsAppMember;
use app\models\PsMember;
use common\MyException;
use Yii;
use service\BaseService;


Class AnswerService extends BaseService
{

    /**
     * 获取用户信息
     * @author yjh
     * @param $params
     * @return int|mixed
     * @throws MyException
     */
    public function getUserInfo($params)
    {
        $app_member = PsAppMember::find()->where(['app_user_id' => $params['user_id']])->one();
        if ($app_member) {
            $member = PsMember::find()->where(['id' => $app_member['member_id']])->one();
            if (empty($member['mobile'])) {
                throw new MyException('该用户没有绑定手机号');
            } else {
                return $member;
            }
        } else {
            throw new MyException('该用户没有绑定手机号');
        }
    }

    /**
     * 获取用户分数
     * @author yjh
     * @param $member_id
     * @return int|mixed
     */
    public function getUserGrade($member_id)
    {
        $answer = Answer::find()->where(['member_id' => $member_id])->one();
        if (empty($answer['grade'])) {
            return -1;
        } else {
            return $answer['grade'];
        }
    }


    /**
     * 保存答题分数
     * @author yjh
     * @param $params
     * @throws MyException
     */
    public function addGrade($params)
    {
        $member = $this->getUserInfo($params);
        $answer = Answer::find()->where(['member_id' => $member['id']])->one();
        if ($answer) {
            throw new MyException('该用户已经答题过');
        }
        $answer = new Answer();
        $answer->member_id = $member['id'];
        $answer->grade = $params['grade'];
        $answer->created_at = time();
        $answer->save();
    }

    /**
     * 获取排行榜
     * @author yjh
     * @param $params
     * @return array
     */
    public function getTopInfo($params)
    {
        $own = [];
        //可能没绑定手机号去看排行榜
        try {
            $member = $this->getUserInfo($params);
            $user = $this->getUserTop($member['id']);
            $own['top'] = $user['top'];
            $own['grade'] = $user['grade'];
        } catch (MyException $e) {
            $own['top'] = 0;
            $own['grade'] = 0;
        }
        $list = $this->getTopList(20);
        return ['list' => $list,'own' => $own];
    }


    /**
     * 获取指定人排行榜
     * @author yjh
     * @param $member_id
     * @return array|\yii\db\ActiveRecord
     */
    public function getUserTop($member_id)
    {
        $all = $this->getTopList();
        $found_key = array_search($member_id, array_column($all, 'member_id'));
        //如果没答题
        if ($found_key !== false) {
            return $all[$found_key];
        } else {
            return ['top' => 0,'grade' => 0];
        }
    }

    /**
     * 获取排行列表
     * @author yjh
     * @param bool $limit
     * @return array|\yii\db\ActiveQuery|\yii\db\ActiveRecord[]
     */
    public function getTopList($limit = false)
    {
        $all = Answer::find()->orderBy('grade desc,created_at desc');
        if ($limit) {
            $all = $all->limit($limit)->asArray()->all();
        } else {
            $all = $all->asArray()->all();
        }
        if (!empty($all)) {
            foreach ($all as $k => &$v) {
                $v['top'] = ++$k;
            }
        }
        return $all;
    }
}