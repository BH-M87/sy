<?php
/**
 * Created by PhpStorm
 * User: wyf
 * Date: 2019/8/23
 * Time: 15:30
 */

namespace app\modules\property\modules\v1\controllers;


use app\modules\property\controllers\BaseController;
use common\core\PsCommon;
use service\message\MessageService;

class MessageController extends BaseController
{
    /**
     * @api 获取消息列表
     * @author wyf
     * @date 2019/6/14
     * @throws \common\MyException
     * @return null|string
     */
    public function actionList()
    {
        if (!isset($this->request_params['message_type'])) {
            return PsCommon::responseFailed("消息类型不能为空");
        }
        if (!in_array($this->request_params['message_type'], [0, 1, 2, 3, 4])) {
            return PsCommon::responseFailed("消息类型错误");
        }
        $result = MessageService::service()->getList($this->request_params, $this->userId);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * @api 获取消息详情
     * @author wyf
     * @date 2019/6/14
     * @return null|string
     * @throws \common\MyException
     */
    public function actionView()
    {
        if (!isset($this->request_params['id'])) {
            return PsCommon::responseFailed("消息编号不能为空");
        }
        $result = MessageService::service()->view($this->request_params['id'], $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }

    /**
     * @api 标记已读/删除消息
     * @author wyf
     * @date 2019/6/14
     * @return null|string
     * @throws \common\MyException
     */
    public function actionOperation()
    {
        $result = MessageService::service()->operation($this->request_params, $this->user_info);
        if ($result['code']) {
            return PsCommon::responseSuccess($result['data']);
        } else {
            return PsCommon::responseFailed($result['msg']);
        }
    }
}