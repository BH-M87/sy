<?php
/**
 * 脚本基类
 * @author shenyang
 * @date 2017/09/28
 */
namespace console\controllers;

use Yii;
use yii\console\Controller;

Class ConsoleController extends Controller
{
    //是否开启文件锁
    public $lock = false;
    public $file;
    public $lockAction = [];
    public $logs = [];

    public function beforeAction($action)
    {
        if(!parent::beforeAction($action)) return false;
        if($this->lock) {//开关
            //加锁控制到action.(lockAction为空则表示所有方法均加锁，否则只有lockAction包含的方法才加锁)
            if(!$this->lockAction || ($this->lockAction && in_array(Yii::$app->controller->action->id, $this->lockAction))) {
                $file = $this->_getFile();
                if(file_exists($file)) {
                    echo 'process is running';
                    return false;
                } else {
                    touch($file);
                }
            }
        }
        return true;
    }

    //文件锁文件名
    private function _getFile()
    {
        $c = Yii::$app->controller->id;
        $a = Yii::$app->controller->action->id;
        $this->file = Yii::$app->basePath.'/runtime/'.$c.'_'.$a.'.run';
        return $this->file;
    }

    public function afterAction($action, $result)
    {
        if(parent::afterAction($action, $result)) return false;

        if(file_exists($this->file)) {
            unlink($this->file);
        }
        //记录日志
        if($this->logs) {
            $msgs = Yii::$app->controller->action->getUniqueId().PHP_EOL;
            $msgs .= implode(PHP_EOL, $this->logs);
            Yii::info($msgs, 'console');
        }
        return true;
    }

    //脚本日志
    protected function log($data) {
        if(is_array($data)) {
            $message = json_encode($data);
        } else {
            $message = $data;
        }
        $this->logs[] = $message;
    }
}
