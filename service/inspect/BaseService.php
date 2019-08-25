<?php
/**
 * Filled
 * @author shenyang
 * @date 2018-04-11
 */

namespace service\inspect;

use service\BaseService as CommonService;
use yii\base\Model;

Class BaseService extends CommonService
{
    /**
     * 返回model validate错误
     * @param Model $model
     * @return string
     */
    public function getError(Model $model)
    {
        $errors = array_values($model->getErrors());
        if(!empty($errors[0][0])) {
            return $errors[0][0];
        }
        return '网络异常';
    }
}
