<?php
/**
 * Many to Many Field Type plugin for Craft CMS 3.x
 *
 * A Field Type plugin for Craft 3 that allows the management of relationships from both sides.
 *
 * @link      https://www.oberon.nl
 * @copyright Copyright (c) 2017 Oberon Amsterdam
 */

namespace OberonAmsterdam\ManyToMany;

use Craft;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use OberonAmsterdam\ManyToMany\fields\Field;
use yii\base\Event;

/**
 * @author    Oberon Amsterdam
 * @package   Plugin
 * @since     1.0.0
 */
class Plugin extends craft\base\Plugin
{
    /** @var Plugin */
    public static $plugin;

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Craft::info(
            Craft::t('craft-manytomany', '{name} plugin loaded', ['name' => $this->name]),
            __METHOD__
        );

        // Register our fields
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = Field::class;
            }
        );
    }
}
