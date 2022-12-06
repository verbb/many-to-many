<?php
namespace verbb\manytomany;

use verbb\manytomany\base\PluginTrait;
use verbb\manytomany\fields\ManyToManyField;

use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;

use yii\base\Event;

class ManyToMany extends Plugin
{
    // Properties
    // =========================================================================

    public $schemaVersion = '2.1.1';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_setLogging();
        $this->_registerFieldTypes();
    }


    // Private Methods
    // =========================================================================

    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = ManyToManyField::class;
        });
    }
}
