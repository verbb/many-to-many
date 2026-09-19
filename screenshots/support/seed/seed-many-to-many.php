/** Seed recipe and ingredient entries related through the real Many to Many field. */

use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Entries;
use craft\helpers\Json;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use verbb\manytomany\fields\ManyToManyField;

$fields = Craft::$app->getFields();
$entries = Craft::$app->getEntries();
$elements = Craft::$app->getElements();
$site = Craft::$app->getSites()->getPrimarySite();

$createSection = static function(string $name, string $handle) use ($entries, $site): array {
    $section = $entries->getSectionByHandle($handle);

    if (!$section) {
        $entryType = new EntryType([
            'name' => $name,
            'handle' => $handle . 'Type',
            'hasTitleField' => true,
        ]);

        $layout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab([
            'name' => Craft::t('app', 'Content'),
            'layout' => $layout,
        ]);
        $tab->setElements([new EntryTitleField()]);
        $layout->setTabs([$tab]);
        $entryType->setFieldLayout($layout);

        if (!$entries->saveEntryType($entryType)) {
            throw new RuntimeException('Unable to save entry type: ' . Json::encode($entryType->getErrors()));
        }

        $section = new Section([
            'name' => $name,
            'handle' => $handle,
            'type' => Section::TYPE_CHANNEL,
        ]);
        $section->setEntryTypes([$entryType]);
        $section->setSiteSettings([
            new Section_SiteSettings([
                'siteId' => $site->id,
                'enabledByDefault' => true,
                'hasUrls' => false,
            ]),
        ]);

        if (!$entries->saveSection($section)) {
            throw new RuntimeException('Unable to save section: ' . Json::encode($section->getErrors()));
        }
    }

    $entryType = $entries->getEntryTypesBySectionId($section->id)[0] ?? null;

    if (!$entryType) {
        throw new RuntimeException("Section {$handle} has no entry type.");
    }

    return [$section, $entryType];
};

[$ingredientsSection, $ingredientsType] = $createSection('Ingredients', 'screenshotIngredients');
[$recipesSection, $recipesType] = $createSection('Recipes', 'screenshotRecipes');

$recipesField = $fields->getFieldByHandle('relatedRecipes');

if (!$recipesField instanceof Entries) {
    $recipesField = new Entries([
        'name' => 'Related recipes',
        'handle' => 'relatedRecipes',
        'sources' => ['section:' . $recipesSection->uid],
        'selectionLabel' => 'Add a recipe',
        'viewMode' => Entries::VIEW_MODE_LIST_INLINE,
        'showSearchInput' => false,
    ]);

    if (!$fields->saveField($recipesField)) {
        throw new RuntimeException('Unable to save Related recipes field: ' . Json::encode($recipesField->getErrors()));
    }
}

$ingredientsField = $fields->getFieldByHandle('relatedIngredients');

if (!$ingredientsField instanceof ManyToManyField) {
    $ingredientsField = new ManyToManyField([
        'name' => 'Related ingredients',
        'handle' => 'relatedIngredients',
        'source' => [
            'type' => 'section',
            'value' => $ingredientsSection->uid,
        ],
        'singleField' => $recipesField->uid,
        'selectionLabel' => 'Add an ingredient',
    ]);

    if (!$fields->saveField($ingredientsField)) {
        throw new RuntimeException('Unable to save Related ingredients field: ' . Json::encode($ingredientsField->getErrors()));
    }
}

$setLayout = static function(EntryType $entryType, $field) use ($entries): void {
    $layout = new FieldLayout(['type' => Entry::class]);
    $tab = new FieldLayoutTab([
        'name' => Craft::t('app', 'Content'),
        'layout' => $layout,
    ]);
    $tab->setElements([new EntryTitleField(), new CustomField($field)]);
    $layout->setTabs([$tab]);
    $entryType->setFieldLayout($layout);

    if (!$entries->saveEntryType($entryType)) {
        throw new RuntimeException('Unable to update entry type layout: ' . Json::encode($entryType->getErrors()));
    }
};

$setLayout($ingredientsType, $recipesField);
$setLayout($recipesType, $ingredientsField);

$saveEntry = static function(Section $section, EntryType $entryType, string $title, string $slug) use ($elements, $site): Entry {
    $entry = Entry::find()
        ->sectionId($section->id)
        ->slug($slug)
        ->siteId($site->id)
        ->status(null)
        ->one();

    if (!$entry) {
        $entry = new Entry([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
            'siteId' => $site->id,
            'slug' => $slug,
            'enabled' => true,
        ]);
    }

    $entry->title = $title;

    if (!$elements->saveElement($entry)) {
        throw new RuntimeException('Unable to save screenshot entry: ' . Json::encode($entry->getErrors()));
    }

    return $entry;
};

$soup = $saveEntry($recipesSection, $recipesType, 'Chicken noodle soup', 'chicken-noodle-soup');
$enchiladas = $saveEntry($recipesSection, $recipesType, 'Chicken enchiladas', 'chicken-enchiladas');

$chicken = $saveEntry($ingredientsSection, $ingredientsType, '1 lb chopped chicken', 'chopped-chicken');
$broth = $saveEntry($ingredientsSection, $ingredientsType, 'Chicken broth', 'chicken-broth');
$peas = $saveEntry($ingredientsSection, $ingredientsType, '1 lb peas', 'peas');
$carrots = $saveEntry($ingredientsSection, $ingredientsType, '1 lb chopped carrots', 'chopped-carrots');

foreach ([$chicken, $broth, $peas, $carrots] as $ingredient) {
    $relatedRecipeIds = $ingredient->id === $chicken->id ? [$soup->id, $enchiladas->id] : [$soup->id];
    $ingredient->setFieldValue('relatedRecipes', $relatedRecipeIds);

    if (!$elements->saveElement($ingredient)) {
        throw new RuntimeException('Unable to save ingredient relationships: ' . Json::encode($ingredient->getErrors()));
    }
}

$ingredientEditPath = parse_url((string)$chicken->getCpEditUrl(), PHP_URL_PATH);
$recipeEditPath = parse_url((string)$soup->getCpEditUrl(), PHP_URL_PATH);

echo Json::encode([
    'ingredientEditRoute' => $ingredientEditPath,
    'recipeEditRoute' => $recipeEditPath,
], JSON_THROW_ON_ERROR);
