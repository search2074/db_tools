<?php

namespace app\controllers;

use Yii;
use app\helpers\Db;
use yii\db\Exception;
use yii\web\Response;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\services\DatabaseService;
use app\services\TableCompareService;
use app\services\DatabaseCompareService;


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
                    'compare-table-data' => ['get'],
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
        $dbCompareService = new DatabaseCompareService($leftDatabaseService, $rightDatabaseService);
        $dbCompareService->compare();

        return $this->render('index', [
            'dbCompareService' => $dbCompareService,
        ]);
    }

    public function actionProcess(){
        Yii::$app->response->format = Response::FORMAT_JSON;

        if(empty(Yii::$app->request->post('left_tables')) && empty(Yii::$app->request->post('right_tables'))){
            return [
                'success' => false,
                'error' => [
                    'type' => 'argument error',
                    'message' => 'wrong params'
                ]
            ];
        }

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
        else {
            return [
                'success' => false,
                'error' => [
                    'type' => 'argument error',
                    'message' => 'left_tables param is not set'
                ]
            ];
        }

//        var_dump(Yii::$app->request->post('left_tables'));
//        var_dump(Yii::$app->request->post('right_tables'));

        return [
            'success' => true
        ];
    }

    public function actionCompareTableData($source_database, $table_name){
        if(empty($source_database) || empty($table_name)){
            return [
                'success' => false,
                'error' => [
                    'type' => 'argument error',
                    'message' => 'wrong params'
                ]
            ];
        }

        try {
            $leftDatabaseService = new DatabaseService('left_db');
            $rightDatabaseService = new DatabaseService('right_db');
            $tableCompareService = new TableCompareService($leftDatabaseService, $rightDatabaseService);
            $tableCompareService->compare($source_database, $table_name);

            return $this->renderAjax('compare_table_data', [
                'tableCompareService' => $tableCompareService,
            ]);
        }
        catch (Exception $error){
            var_dump($error);die;
        }
    }
}
