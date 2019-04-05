<?php
namespace app\services;

use Yii;

class PostActionsService
{
    public static function run(){
        $result = "";

        if(!empty(Yii::$app->params['post_actions'])){
            foreach (Yii::$app->params['post_actions'] as $postAction) {
                if(strstr($postAction, "exec")){
                    $actions = explode('exec', $postAction);

                    if(empty($actions[1])){
                        continue;
                    }

                    $result .= shell_exec($actions[1]) . PHP_EOL;
                }
            }
        }

        return $result;
    }
}