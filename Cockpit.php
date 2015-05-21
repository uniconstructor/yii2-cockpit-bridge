<?php

namespace omnilight\cockpit;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;


/**
 * Class Cockpit
 */
class Cockpit extends Component
{
    /**
     * @var string File where will be stored cockpit configuration
     */
    public $cockpitConfigFile = '@runtime/cockpit_config.php';
    /**
     * @var string Cockpit storage path
     */
    public $cockpitStoragePath = '@data/cockpit';
    /**
     * @var array Cockpit config options. This file will be merged with defaults
     */
    public $config =[];
    /**
     * @var string - relative url (example: module/controller/action)
     */
    public $baseRoute;
    /**
     * @var string - relative url (example: module/controller/action)
     */
    public $baseUrl;

    public function init()
    {
        $cockpitStorage = \Yii::getAlias($this->cockpitStoragePath);
        $cockpitAssets  = \Yii::$app->getAssetManager()->publish(\Yii::getAlias('@vendor/aheinze/cockpit/assets'), ['forceCopy' => false]);
        $cockpitCache   = \Yii::$app->getAssetManager()->publish(\Yii::getAlias('@vendor/aheinze/cockpit/storage/cache'), ['forceCopy' => true]);
        
        if ( $this->baseRoute )
        {
            define('COCKPIT_BASE_ROUTE' , $this->baseRoute);
        }
        if ( $this->baseUrl )
        {
            define('COCKPIT_BASE_URL' , $this->baseUrl);
        }
        $defaults = [
            'database' => [ "server" => "mongolite://".($cockpitStorage."/data"), "options" => ["db" => "cockpitdb"] ],
            'paths' => [
                '#root'    => \Yii::getAlias('@app'),
                'assets'  => $cockpitAssets[0],
                'storage' => $cockpitStorage,
                '#backups'=> $cockpitStorage.'/backups',
                'data'    => $cockpitStorage.'/data',
                'custom'  => \Yii::getAlias('@vendor/aheinze/cockpit-i18n'),
                'cache'   => $cockpitCache[0],
                'tmp'     => \Yii::getAlias('@runtime/cockpit/cache/tmp'),
            ]
        ];

        $configFile = \Yii::getAlias($this->cockpitConfigFile);
        define('COCKPIT_CONFIG_PATH', $configFile);

        $config = ArrayHelper::merge($defaults, $this->config);
        $configData = var_export($config, true);
        FileHelper::createDirectory(dirname($configFile));
        file_put_contents($configFile, "<?php\nreturn ".$configData.';');
        
        $includePath = \Yii::getAlias('@cockpit/vendor');
        set_include_path(get_include_path().PATH_SEPARATOR.$includePath);
    }

    public function run()
    {
        define('COCKPIT_ADMIN', 1);
        if (!isset($_SERVER['COCKPIT_URL_REWRITE'])) {
            $_SERVER['COCKPIT_URL_REWRITE'] = 'Off';
        }
        //date_default_timezone_set('UTC');
        if (PHP_SAPI == 'cli-server' && is_file(__DIR__.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
            return false;
        }
        
        require(\Yii::getAlias('@vendor/aheinze/cockpit/bootstrap.php'));
        /** @var $cockpit */
        $cockpit->on("after", function() {
            switch ($this->response->status) {
                case 500:
                    if ($this['debug']) {
                        if ($this->req_is('ajax')) {
                            $this->response->body = json_encode(['error' => json_decode($this->response->body, true)]);
                        } else {
                            $this->response->body = $this->render("cockpit:views/errors/500-debug.php", ['error' => json_decode($this->response->body, true)]);
                        }
                    } else {
                        if ($this->req_is('ajax')) {
                            $this->response->body = '{"error": "500", "message": "system error"}';
                        } else {
                            $this->response->body = $this->view("cockpit:views/errors/500.php");
                        }
                    }
                    break;
                case 404:
                    if ($this->req_is('ajax')) {
                        $this->response->body = '{"error": "404", "message":"File not found"}';
                    } else {
                        $this->response->body = $this->view("cockpit:views/errors/404.php");
                    }
                    break;
            }
        });
        // run backend
        $cockpit->set('route', COCKPIT_ADMIN_ROUTE)->trigger("admin.init")->run();
    }
}
