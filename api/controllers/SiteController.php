<?php
namespace api\controllers;

use yii\web\Controller;

/**
 * Site controller
 */
class SiteController extends Controller
{

    public $layout = false;
    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return 'this is '.\Yii::$app->id;
    }
}
