<?php

namespace omnilight\cockpit;
use yii\base\Action;
use yii\di\Instance;


/**
 * Class CockpitAdminAction
 */
class CockpitAdminAction extends Action
{
    /**
     * Name of application component or config for Cockpit component
     * @var string|Cockpit
     */
    public $cockpit = 'cockpit';

    public function init()
    {
        $this->cockpit = Instance::ensure($this->cockpit, Cockpit::className());

        parent::init();
    }


    public function run()
    {
        $this->cockpit->run();
    }
}