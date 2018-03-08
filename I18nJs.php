<?php

namespace w3lifer\yii2;

use w3lifer\phpHelper\PhpHelper;
use Yii;
use yii\base\BaseObject;
use yii\helpers\Json;
use yii\web\View;

/**
 * @see https://github.com/yiisoft/yii2/issues/274
 */
class I18nJs extends BaseObject
{
    /**
     * @var string The path to the JS file relative to the `@webroot` directory.
     */
    public $jsFilename = 'js/i18n.js';

    /**
     * @var string
     */
    private $jsFilenameOnServer;

    /**
     * @var string
     */
    private $filenameForSavingModificationTime;

    /**
     * @var array
     */
    private $basePaths = [];

    /**
     * @var array
     */
    private $filenames = [];

    /**
     * @var integer
     */
    private $savedModificationTime;

    /**
     * @var integer
     */
    private $currentModificationTime;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->jsFilenameOnServer =
            Yii::getAlias('@webroot') . '/' . $this->jsFilename;
        $dirname = dirname($this->jsFilenameOnServer);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }
        $this->filenameForSavingModificationTime =
            Yii::getAlias('@runtime') . '/i18n-js-modification-time';
        $this->basePaths = $this->getBasePaths();
        $this->filenames = $this->getFilenames();
        $this->savedModificationTime = $this->getSavedModificationTime();
        $this->currentModificationTime = $this->getCurrentModificationTime();
        if (
            !file_exists($this->jsFilenameOnServer) ||
            !$this->savedModificationTime ||
            $this->savedModificationTime !== $this->currentModificationTime
        ) {
            $this->saveJsFile();
            $this->saveModificationTime();
        }
        Yii::$app->view->registerJsFile(
            '/' . $this->jsFilename . '?v=' . $this->currentModificationTime
        );
        $this->registerJsScript();
    }

    private function getBasePaths()
    {
        $basePaths = [];
        foreach (Yii::$app->i18n->translations as $category => $translation) {
            if ($category !== 'yii') {
                if (is_array($translation)) {
                    $basePaths[] =
                        isset($translation['basePath'])
                            ? Yii::getAlias($translation['basePath'])
                            : Yii::getAlias('@app/messages');
                } else {
                    $basePaths[] = Yii::getAlias($translation->basePath);
                }
            }
        }
        return array_unique($basePaths);
    }

    private function getFilenames()
    {
        $filenames = [];
        foreach ($this->basePaths as $basePath) {
            foreach (
                PhpHelper::get_files_in_directory(
                    $basePath,
                    true,
                    ['php']
                ) as $filename
            ) {
                $filenames[] = $filename;
            }
        }
        return $filenames;
    }

    private function getCurrentModificationTime()
    {
        $commonModificationTime = 0;
        foreach ($this->filenames as $filename) {
            $commonModificationTime += filemtime($filename);
        }
        return $commonModificationTime;
    }

    private function getSavedModificationTime()
    {
        $modificationTime = 0;
        if (file_exists($this->filenameForSavingModificationTime)) {
            $modificationTime =
                (int)
                    file_get_contents($this->filenameForSavingModificationTime);
        }
        return $modificationTime;
    }

    private function saveJsFile()
    {
        $result = [];
        foreach ($this->basePaths as $basePath) {
            foreach ($this->filenames as $filename) {
                $signature = str_replace($basePath . '/', '', $filename);
                $signature = substr($signature, 0, -4);
                preg_match_all('=^([-a-z]+)/(.+)=i', $signature, $matches);
                /** @noinspection PhpIncludeInspection */
                $result[$matches[1][0]][$matches[2][0]] = include $filename;
            }
        }
        return
            file_put_contents(
                $this->jsFilenameOnServer,
                'var YII_I18N_JS = ' . Json::encode($result) . ';' . "\n"
            );
    }

    private function saveModificationTime()
    {
        file_put_contents(
            $this->filenameForSavingModificationTime,
            $this->currentModificationTime . "\n"
        );
    }

    private function registerJsScript()
    {
        $sourceLanguage = strtolower(Yii::$app->sourceLanguage);
        $js = <<<JS
(function () {
  if (!('t' in window.yii)) {
    var language = document.documentElement.lang;
    if (!language) {
      throw new Error(
        'You must specify the "lang" attribute for the <html> element'
      );
    }
    yii.t = function (category, message, params) {
      if (
        language === "{$sourceLanguage}" || 
        !YII_I18N_JS ||
        !YII_I18N_JS[language] ||
        !YII_I18N_JS[language][category] ||
        !YII_I18N_JS[language][category][message]
      ) {
        return message;
      }
      var translatedMessage = YII_I18N_JS[language][category][message];
      if (params) {
        Object.keys(params).map(function (key) {
          var escapedParam =
            // https://stackoverflow.com/a/6969486/4223982
            key.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, '\\$&');
          var regExp = new RegExp('\\\{' + escapedParam + '\\\}', 'g');
          translatedMessage = translatedMessage.replace(regExp, params[key]);
        });
      }
      return translatedMessage;
    };
  }
})();
JS;
        Yii::$app->view->registerJs($js, View::POS_END);
    }
}
