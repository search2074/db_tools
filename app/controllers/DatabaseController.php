<?php

namespace app\controllers;

use Yii;
use app\helpers\Db;
use yii\web\Response;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\services\DatabaseService;


class DatabaseController extends Controller
{
    public $title = 'Database';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            // only auth user for all actions
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'process' => ['post'],
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
        $leftDbDataProvider = $leftDatabaseService->getDataProvider();
        $rightDatabaseService = new DatabaseService('right_db');
        $rightDbDataProvider = $rightDatabaseService->getDataProvider();
        $leftDatabaseDiff = $leftDatabaseService->getDiff($rightDbDataProvider);
        $rightDatabaseDiff = $rightDatabaseService->getDiff($leftDbDataProvider);

        return $this->render('index', [
            'leftDbDataProvider' => $leftDbDataProvider,
            'rightDbDataProvider' => $rightDbDataProvider,
            'leftDatabaseDiff' => $leftDatabaseDiff,
            'rightDatabaseDiff' => $rightDatabaseDiff
        ]);
    }

    public function actionProcess(){
        Yii::$app->response->format = Response::FORMAT_JSON;

        if(!empty(Yii::$app->request->post('left_tables'))){
            foreach (Yii::$app->request->post('left_tables') as $table) {
                try {
                    Db::export('left_db', $table);
                    Db::import('right_db', $table);
                }
                catch (\yii\db\Exception $exception){
                    return [
                        'success' => false,
                        'error' => [
                            'type' => 'database error',
                            'message' => $exception->getMessage()
                        ]
                    ];
                }
                catch (\yii\base\InvalidArgumentException $exception){
                    return [
                        'success' => false,
                        'error' => [
                            'type' => 'argument error',
                            'message' => $exception->getMessage()
                        ]
                    ];
                }
                catch (\yii\base\Exception $exception){
                    return [
                        'success' => false,
                        'error' => [
                            'type' => 'base error',
                            'message' => $exception->getMessage()
                        ]
                    ];
                }
            }
        }

//        var_dump(Yii::$app->request->post('left_tables'));
//        var_dump(Yii::$app->request->post('right_tables'));

        return [
            'success' => true
        ];
    }
}
