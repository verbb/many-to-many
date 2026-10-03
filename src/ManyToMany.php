<?php
namespace verbb\manytomany;

use verbb\manytomany\base\PluginTrait;
use verbb\manytomany\fields\ManyToManyField;

use Craft;
use craft\base\Plugin;
use craft\errors\InvalidElementException;
use craft\events\DraftEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Drafts;
use craft\services\Fields;

use yii\base\Event;

class ManyToMany extends Plugin
{
    // Properties
    // =========================================================================

    public string $schemaVersion = '2.1.1';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerDraftEvents();
        $this->_registerFieldTypes();
    }


    // Private Methods
    // =========================================================================

    private function _registerDraftEvents(): void
    {
        Event::on(Drafts::class, Drafts::EVENT_BEFORE_APPLY_DRAFT, function(DraftEvent $event) {
            $draft = $event->draft;

            if (!$draft) {
                return;
            }

            foreach ($draft->getFieldLayout()?->getCustomFields() ?? [] as $field) {
                $isModified = $field instanceof ManyToManyField && $draft->isFieldModified($field->handle, true);
                $isNewRelationship = $field instanceof ManyToManyField &&
                    $event->canonical === $draft &&
                    !empty($field->serializeValue($draft->getFieldValue($field->handle), $draft));

                if (
                    $field instanceof ManyToManyField &&
                    ($isModified || $isNewRelationship) &&
                    !$field->prepareDraftApplication($draft)
                ) {
                    throw new InvalidElementException($draft, Craft::t('manytomany', 'Draft relationship changes are not authorized.'));
                }
            }
        });
    }

    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = ManyToManyField::class;
        });
    }
}
