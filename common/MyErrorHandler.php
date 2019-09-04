<?php
/**
 * 扩展exception处理类
 * @author shenyang
 */

namespace common;

use common\core\F;
use common\core\PsCommon;
use yii\web\ErrorHandler;
use yii\web\Response;
use yii\web\HttpException;

class MyErrorHandler extends ErrorHandler
{

    public function renderException($exception)
    {

        if ($exception instanceof HttpException) {
            $result['code'] = $exception->statusCode;
            $result['msg'] = $exception->getMessage();
            \Yii::$app->response->format = Response::FORMAT_JSON;
            \Yii::$app->response->content = json_encode($result,JSON_UNESCAPED_UNICODE);
            return \Yii::$app->response->send();
        }
        if ($exception instanceof \Exception) {
            $message = $exception->getMessage();
        } else {
            $message = 'Error';
        }
        $status = F::getSmallStatus();
        if($status){
            return F::apiFailed($message);
        }else{
            return PsCommon::responseFailed($message);
        }

    }
}
