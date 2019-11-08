<?php


namespace app\commands;

use Yii;
use yii\console\Controller;

Class TmpController extends Controller
{


   public function actionTest()
   {
       file_put_contents('./1.txt',time());
   }
}
