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
use yii\base\Event;
use Craft\base\Plugin as BasePlugin;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;
use OberonAmsterdam\ManyToMany\Fields\ManyToManyField;
use OberonAmsterdam\ManyToMany\Services\ManyToManyService;

/**
 * @author    Oberon Amsterdam
 * @package   ManyToManyService
 * @since     1.0.0
 *
 * @property ManyToManyService $service
 */
class Plugin extends BasePlugin
{
    /** @var self */
    public static $plugin;

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'service' => ManyToManyService::class,
        ]);

        // Register our fields
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = ManyToManyField::class;
            }
        );

        Craft::info($this->name . ' plugin loaded', __METHOD__);
    }
}
