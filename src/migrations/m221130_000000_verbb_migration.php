<?php
namespace verbb\manytomany\migrations;

use verbb\manytomany\fields\ManyToManyField;

use Craft;
use craft\db\Migration;

class m221130_000000_verbb_migration extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->update('{{%fields}}', ['type' => ManyToManyField::class], ['type' => 'Page8\ManyToMany\fields\ManyToManyField']);

        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('plugins.single-cat.schemaVersion', true);

        if (version_compare($schemaVersion, '2.0.0', '>=')) {
            return true;
        }

        $fields = $projectConfig->get('fields') ?? [];

        $fieldMap = [
            'Page8\ManyToMany\fields\ManyToManyField' => ManyToManyField::class,
        ];

        foreach ($fields as $fieldUid => $field) {
            $type = $field['type'] ?? null;

            if (isset($fieldMap[$type])) {
                $field['type'] = $fieldMap[$type];

                $projectConfig->set('fields.' . $fieldUid, $field);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m221130_000000_verbb_migration cannot be reverted.\n";
        return false;
    }
}