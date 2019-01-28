<?php

namespace app\controllers;

use Yii;
use yii\web\Response;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\services\DatabaseService;


class DatabaseController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Lists databases
     * @return mixed
     */
    public function actionIndex()
    {
        $leftDatabaseService = new DatabaseService('left_db');
        $rightDatabaseService = new DatabaseService('right_db');

        return $this->render('index', [
            'leftDbDataProvider' => $leftDatabaseService->getDataProvider(),
            'rightDbDataProvider' => $leftDatabaseService->getDataProvider(),
        ]);
    }
}
