<?php
namespace verbb\manytomany\migrations;

use verbb\manytomany\fields\ManyToManyField;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

class m221130_100000_settings_uid extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.manytomany.schemaVersion', true);

        if (version_compare($schemaVersion, '2.1.0', '>=')) {
            return true;
        }

        $fields = (new Query())
            ->from('{{%fields}}')
            ->where(['type' => ManyToManyField::class])
            ->all();

        foreach ($fields as $fieldData) {
            $settings = Json::decode($fieldData['settings']);
            $singleFieldId = $settings['singleField'] ?? null;
            $sourceId = $settings['source']['value'] ?? null;

            if ($singleFieldId) {
                $singleField = Craft::$app->getFields()->getFieldById($singleFieldId);

                if ($singleField) {
                    $settings['singleField'] = $singleField->uid;
                }
            }

            if ($sourceId) {
                $source = Craft::$app->getSections()->getSectionById($sourceId);

                if ($source) {
                    $settings['source']['value'] = $source->uid;
                }
            }
            
            $this->update('{{%fields}}', ['settings' => Json::encode($settings)], ['id' => $fieldData['id']]);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m221130_100000_settings_uid cannot be reverted.\n";
        return false;
    }
}