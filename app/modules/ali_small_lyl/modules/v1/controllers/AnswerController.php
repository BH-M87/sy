<?php
/**
 * 消防答题
 * User: yjh
 * Date: 2019/11/4
 * Time: 11:31
 */
namespace app\modules\ali_small_lyl\modules\v1\controllers;

use app\modules\ali_small_lyl\controllers\UserBaseController;
use common\core\F;
use common\core\PsCommon;
use common\MyException;
use service\small\AnswerService;


class AnswerController extends UserBaseController
{

    /**
     * 获取用户积分
     * @author yjh
     * @throws MyException
     */
    public function actionGetUserInfo()
    {
        if (empty($this->params['user_id'])) throw new MyException('用户id不能为空');
        $member = AnswerService::service()->getUserInfo($this->params);
        $result = AnswerService::service()->getUserGrade($member['id']);
        return F::apiSuccess(['grade' => $result]);
    }

    /**
     * 提交答题分数
     * @author yjh
     * @throws MyException
     */
    public function actionPostNumber()
    {
        if (empty($this->params['user_id']) || !isset($this->params['grade'])) throw new MyException('用户id或分数不能为空');
        AnswerService::service()->addGrade($this->params);
        return F::apiSuccess();
    }

    /**
     * 排行榜
     * @author yjh
     * @throws MyException
     */
    public function actionGetTop()
    {
        if (empty($this->params['user_id'])) throw new MyException('用户id不能为空');
        $result = AnswerService::service()->getTopInfo($this->params);
        return F::apiSuccess($result);
    }

    public function actionGetList()
    {
        $result = AnswerService::service()->getList();
        return F::apiSuccess($result);
    }

    public function actionGetTime()
    {
        $result = AnswerService::service()->getTime();
        return F::apiSuccess($result);
    }
}