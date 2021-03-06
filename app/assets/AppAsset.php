<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use Yii;
use yii\web\AssetBundle;
use yii\helpers\FileHelper;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
        'js/app.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];

    public function init()
    {
        parent::init();

        if (!empty(Yii::$app->params['baseUrl'])) {
            $this->baseUrl = Yii::$app->params['baseUrl'];
        }

        $this->setVersioned('css');
        $this->setVersioned('js');
    }

    /**
     * Add versioned label to resources files
     * @param string $property
     */
    private function setVersioned($property = 'css'){
        if(empty($this->$property)){
            return;
        }

        foreach ($this->$property as $i => $value) {
            $file_path = FileHelper::normalizePath(Yii::getAlias($this->basePath . DIRECTORY_SEPARATOR . $value));

            if(file_exists($file_path)){
                $hash = hash_file('crc32', $file_path);
                $this->$property[$i] = "{$value}?v={$hash}";
            }
        }
    }
}
