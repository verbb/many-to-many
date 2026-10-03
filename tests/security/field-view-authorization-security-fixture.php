<?php

declare(strict_types=1);

namespace yii\db {
    class Expression
    {
    }

    class Schema
    {
        public const TYPE_JSON = 'json';
    }
}

namespace craft\base {
    interface EagerLoadingFieldInterface
    {
    }

    interface ElementInterface
    {
    }

    interface PreviewableFieldInterface
    {
    }

    class Component
    {
    }

    class Field
    {
        public string $handle = 'relations';
        public string $uid = 'many-to-many-field';

        public function __construct(array $config = [])
        {
            foreach ($config as $name => $value) {
                $this->{$name} = $value;
            }
        }

        public function beforeElementSave(ElementInterface $element, bool $isNew): bool
        {
            return true;
        }

        public function afterElementSave(ElementInterface $element, bool $isNew): void
        {
        }

        protected function defineRules(): array
        {
            return [];
        }
    }
}

namespace craft\elements\db {
    interface ElementQueryInterface
    {
    }
}

namespace craft\elements {
    use craft\base\ElementInterface;

    class ElementCollection
    {
        public function __construct(private array $elements)
        {
        }

        public function all(): array
        {
            return $this->elements;
        }
    }

    class Entry implements ElementInterface
    {
    }

    class User implements ElementInterface
    {
        public const STATUS_ACTIVE = 'active';

        public function __construct(
            public int $id,
            private string $status = self::STATUS_ACTIVE,
        ) {
        }

        public function getStatus(): string
        {
            return $this->status;
        }
    }
}

namespace craft\errors {
    class InvalidElementException extends \RuntimeException
    {
    }
}

namespace craft\fields {
    use Fixture\Owner;

    class BaseRelationField
    {
        public const VIEW_MODE_LIST = 'list';
        public const VIEW_MODE_LIST_INLINE = 'list-inline';
        public const VIEW_MODE_CARDS = 'cards';
        public const VIEW_MODE_CARDS_GRID = 'card-grid';

        public int $id = 50;
        public string $uid = 'associated-field';
        public string $handle = 'inverseRelations';

        public static function elementType(): string
        {
            return Owner::class;
        }

        public function getIsTranslatable(mixed $element = null): bool
        {
            return false;
        }
    }
}

namespace craft\helpers {
    class ArrayHelper
    {
    }

    class Cp
    {
        public static array $previewElements = [];

        public static function elementPreviewHtml(array $elements): string
        {
            self::$previewElements = $elements;

            return 'preview';
        }
    }

    class ElementHelper
    {
        public static function isDraftOrRevision(object $element): bool
        {
            return $element->getIsDraft() || $element->getIsRevision();
        }
    }

    class Gql
    {
    }

    class Html
    {
        public static function id(string $value): string
        {
            return $value;
        }
    }

    class Json
    {
        public static function decode(string $value): mixed
        {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        public static function encode(mixed $value): string
        {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }
    }

    class StringHelper
    {
    }
}

namespace craft\models {
    class Section
    {
    }
}

namespace craft\services {
    class Gql
    {
        public const GRAPHQL_COMPLEXITY_EAGER_LOAD = 1;
    }
}

namespace GraphQL\Type\Definition {
    class Type
    {
    }
}

namespace verbb\manytomany\sources {
    use craft\base\ElementInterface;
    use Fixture\LinkedElement;

    interface RelationSourceInterface
    {
    }

    class FakeAdapter implements RelationSourceInterface
    {
        public array $elements = [];
        public array $relatedElements = [];

        public function defaultSelectionLabel(): string
        {
            return 'Add an element';
        }

        public function elementType(): string
        {
            return LinkedElement::class;
        }

        public function findElements(array $ids, ElementInterface $owner, string $sourceValue): array
        {
            $elements = [];

            foreach ($ids as $id) {
                if (isset($this->elements[$id])) {
                    $elements[] = $this->elements[$id];
                }
            }

            return $elements;
        }

        public function findRelatedElements(ElementInterface $owner, string $sourceValue, int $fieldId, bool $anyStatus = false): array
        {
            return $this->relatedElements;
        }

        public function selectorCriteria(string $sourceValue): array
        {
            return [];
        }

        public function selectorSources(string $sourceValue): array
        {
            return ['source:test'];
        }

        public function sourceExists(string $sourceValue): bool
        {
            return $sourceValue === 'source-uid';
        }
    }

    class RelationSources
    {
        public static ?RelationSourceInterface $adapter = null;

        public static function all(): array
        {
            return [];
        }

        public static function get(string $elementType): ?RelationSourceInterface
        {
            return self::$adapter;
        }
    }
}

namespace verbb\manytomany {
    class ManyToMany
    {
        public static object $plugin;
    }
}

namespace Fixture {
    use craft\base\ElementInterface;
    use craft\fields\BaseRelationField;

    class LinkedElement implements ElementInterface
    {
        public array $relationIds = [];

        public function __construct(
            public int $id,
            public bool $viewable,
            public bool $saveable = true,
            public array $saveableUserIds = [],
        ) {
        }

        public function getFieldValue(string $handle): array
        {
            return $this->relationIds;
        }

        public function getFieldLayout(): FakeFieldLayout
        {
            return new FakeFieldLayout();
        }

        public function setFieldValue(string $handle, array $value): void
        {
            $this->relationIds = $value;
        }
    }

    class Owner implements ElementInterface
    {
        public array $errors = [];
        public int $id = 99;
        public string $uid;
        public int $siteId = 1;
        public ?self $duplicateOf = null;

        public function __construct(
            public bool $draft = false,
            private bool $revision = false,
            private bool $dirty = false,
            private array $fieldValue = [],
            public ?self $canonical = null,
            ?string $uid = null,
        ) {
            $this->uid = $uid ?? 'owner-uid';
        }

        public function addError(string $attribute, string $message): void
        {
            $this->errors[$attribute][] = $message;
        }

        public function getCanonicalId(): int
        {
            return 99;
        }

        public function getCanonical(): self
        {
            return $this->canonical ?? $this;
        }

        public function getFieldLayout(): FakeFieldLayout
        {
            return new FakeFieldLayout();
        }

        public function getFieldValue(string $handle): array
        {
            return $this->fieldValue;
        }

        public function getIsDraft(): bool
        {
            return $this->draft;
        }

        public function getIsRevision(): bool
        {
            return $this->revision;
        }

        public function isFieldDirty(string $handle): bool
        {
            return $this->dirty;
        }

        public function isFieldModified(string $handle, bool $anySite = false): bool
        {
            return $this->dirty;
        }

        public function setFieldValue(string $handle, array $value): void
        {
            $this->fieldValue = $value;
        }
    }

    class FakeApp
    {
        public function __construct(
            private FakeUserComponent $user,
            private FakeElementsService $elements,
            private FakeFieldsService $fields,
            private FakeView $view,
            private FakeSecurity $security,
            private FakeUsersService $users,
        ) {
        }

        public function getElements(): FakeElementsService
        {
            return $this->elements;
        }

        public function getEntries(): FakeEntriesService
        {
            return new FakeEntriesService();
        }

        public function getFields(): FakeFieldsService
        {
            return $this->fields;
        }

        public function getUser(): FakeUserComponent
        {
            return $this->user;
        }

        public function getSecurity(): FakeSecurity
        {
            return $this->security;
        }

        public function getUsers(): FakeUsersService
        {
            return $this->users;
        }

        public function getView(): FakeView
        {
            return $this->view;
        }
    }

    class FakeElementsService
    {
        public function canSave(LinkedElement $element, object $user): bool
        {
            return $element->saveable && (empty($element->saveableUserIds) || in_array($user->id, $element->saveableUserIds, true));
        }

        public function canView(LinkedElement $element, object $user): bool
        {
            return $element->viewable;
        }

        public function invalidateCachesForElement(object $element): void
        {
        }

        public function saveElement(LinkedElement $element): bool
        {
            return true;
        }
    }

    class FakeEntriesService
    {
        public function getSectionByUid(string $uid): null
        {
            return null;
        }
    }

    class FakeFieldLayout
    {
        public function getFieldByUid(string $uid): BaseRelationField
        {
            return new BaseRelationField();
        }
    }

    class FakeFieldsService
    {
        public function getFieldByUid(string $uid): BaseRelationField
        {
            return new BaseRelationField();
        }
    }

    class FakeUserComponent
    {
        public function __construct(public ?object $identity)
        {
        }

        public function getIdentity(): ?object
        {
            return $this->identity;
        }
    }

    class FakeSecurity
    {
        private const KEY = 'fixture-security-key';

        public function hashData(string $data): string
        {
            return hash_hmac('sha256', $data, self::KEY) . $data;
        }

        public function validateData(string $data): string|false
        {
            $hash = substr($data, 0, 64);
            $value = substr($data, 64);

            return hash_equals(hash_hmac('sha256', $value, self::KEY), $hash) ? $value : false;
        }
    }

    class FakeUsersService
    {
        public function __construct(public array $users = [])
        {
        }

        public function getUserById(int $id): ?object
        {
            return $this->users[$id] ?? null;
        }
    }

    class FakeView
    {
        public array $params = [];

        public function renderTemplate(string $template, array $params): string
        {
            $this->params = $params;

            return 'input';
        }
    }
}

namespace {
    use craft\elements\ElementCollection;
    use craft\elements\User;
    use craft\helpers\Cp;
    use Fixture\FakeApp;
    use Fixture\FakeElementsService;
    use Fixture\FakeFieldsService;
    use Fixture\FakeSecurity;
    use Fixture\FakeUserComponent;
    use Fixture\FakeUsersService;
    use Fixture\FakeView;
    use Fixture\LinkedElement;
    use Fixture\Owner;
    use verbb\manytomany\fields\ManyToManyField;
    use verbb\manytomany\services\Service;
    use verbb\manytomany\sources\FakeAdapter;
    use verbb\manytomany\sources\RelationSources;

    class Craft
    {
        public static FakeApp $app;

        public static function t(string $category, string $message, array $params = []): string
        {
            return $message;
        }
    }

    require dirname(__DIR__, 2) . '/src/fields/ManyToManyField.php';
    require dirname(__DIR__, 2) . '/src/services/Service.php';

    class TestManyToManyField extends ManyToManyField
    {
        public function renderInput(mixed $value, Owner $element): string
        {
            return $this->inputHtml($value, $element, false);
        }
    }

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    function elementIds(array $elements): array
    {
        return array_map(fn(LinkedElement $element): int => $element->id, $elements);
    }

    function createField(): TestManyToManyField
    {
        return new TestManyToManyField([
            'handle' => 'relations',
            'uid' => 'many-to-many-field',
            'singleField' => 'associated-field',
            'source' => ['type' => 'section', 'value' => 'source-uid'],
        ]);
    }

    $originUser = new User(10);
    $publisher = new User(20);
    $user = new FakeUserComponent($originUser);
    $users = new FakeUsersService([
        10 => $originUser,
        20 => $publisher,
    ]);
    $view = new FakeView();
    Craft::$app = new FakeApp($user, new FakeElementsService(), new FakeFieldsService(), $view, new FakeSecurity(), $users);

    $service = new Service();
    \verbb\manytomany\ManyToMany::$plugin = new class($service) {
        public function __construct(private Service $service)
        {
        }

        public function getService(): Service
        {
            return $this->service;
        }
    };

    $visible = new LinkedElement(1, true);
    $hidden = new LinkedElement(2, false);
    $adapter = new FakeAdapter();
    $adapter->elements = [1 => $visible, 2 => $hidden];
    $adapter->relatedElements = [$visible, $hidden];
    RelationSources::$adapter = $adapter;

    $field = new TestManyToManyField([
        'handle' => 'relations',
        'singleField' => 'associated-field',
        'source' => ['type' => 'section', 'value' => 'source-uid'],
    ]);

    $draft = new Owner(draft: true);
    $normalized = $field->normalizeValueFromRequest(['add' => ['1', '2']], $draft);
    assertSame([], $normalized, 'A mixed authorized and unauthorized submission must not echo a partial selection.');
    assertSame(false, $field->beforeElementSave($draft, false), 'A mixed authorized and unauthorized draft selection must be rejected.');
    assertSame(['One or more selected elements are invalid.'], $draft->errors['relations'] ?? [], 'Unauthorized IDs must use the generic invalid-selection error.');

    $field = new TestManyToManyField([
        'handle' => 'relations',
        'singleField' => 'associated-field',
        'source' => ['type' => 'section', 'value' => 'source-uid'],
    ]);
    $draft = new Owner(draft: true);
    $normalized = $field->normalizeValueFromRequest(['add' => ['1']], $draft);
    assertSame([1], elementIds($normalized), 'An authorized selection must remain available to the field.');
    assertSame(true, $field->beforeElementSave($draft, false), 'An authorized draft selection must remain valid.');

    $field = new TestManyToManyField([
        'handle' => 'relations',
        'singleField' => 'associated-field',
        'source' => ['type' => 'section', 'value' => 'source-uid'],
    ]);
    $draft = new Owner(draft: true, fieldValue: [$hidden]);
    $normalized = $field->normalizeValueFromRequest(['add' => ['1']], $draft);
    assertSame([1, 2], elementIds($normalized), 'An existing hidden draft relation must remain available for server-side serialization.');
    assertSame([1, 2], $field->serializeValue($normalized, $draft), 'Saving unrelated draft changes must preserve an existing hidden relation.');
    $field->renderInput($normalized, $draft);
    assertSame([1], elementIds($view->params['value']), 'A preserved hidden draft relation must still be omitted from Control Panel rendering.');

    $field->getPreviewHtml(new ElementCollection([$visible, $hidden]), new Owner());
    assertSame([1], elementIds(Cp::$previewElements), 'Field previews must omit elements the current user cannot view.');

    $field->renderInput([$visible, $hidden], new Owner());
    assertSame([1], elementIds($view->params['value']), 'Control Panel inputs must omit existing elements the current user cannot view.');

    $user->identity = null;
    $adapter->relatedElements = [];
    $field = new TestManyToManyField([
        'handle' => 'relations',
        'singleField' => 'associated-field',
        'source' => ['type' => 'section', 'value' => 'source-uid'],
    ]);
    $draft = new Owner(draft: true);
    assertSame([], $field->normalizeValueFromRequest(['add' => []], $draft), 'An empty selection must remain empty without an authenticated identity.');
    assertSame(true, $field->beforeElementSave($draft, false), 'An empty selection must remain valid without an authenticated identity.');

    $user->identity = $originUser;
    $adapter->relatedElements = [$visible, $hidden];
    $changes = $service->prepareRelationshipChanges($field, new Owner(), [1]);
    assertSame([], $changes, 'Omitted existing relations hidden from the editor must remain unchanged.');

    $changes = $service->prepareRelationshipChanges($field, new Owner(), []);
    assertSame([1], array_map(fn(array $change): int => $change['source']->id, $changes ?? []), 'A visible relation must remain removable while a hidden relation is preserved.');

    $adapter->relatedElements = [];
    $publisherOnly = new LinkedElement(3, true, true, [20]);
    $adapter->elements[3] = $publisherOnly;
    $field = createField();
    $draft = new Owner(draft: true, canonical: new Owner(), uid: 'unauthorized-draft');
    $normalized = $field->normalizeValueFromRequest(['add' => ['3']], $draft);
    assertSame([3], elementIds($normalized), 'A viewable draft selection must normalize before save authorization runs.');
    assertSame(false, $field->beforeElementSave($draft, false), 'A draft author must not select an element they cannot save.');
    assertSame(['You are not authorized to update the selected elements.'], $draft->errors['relations'] ?? [], 'Draft save authorization failures must remain generic.');

    $originOnly = new LinkedElement(4, true, true, [10]);
    $adapter->elements[4] = $originOnly;
    $field = createField();
    $canonical = new Owner(uid: 'canonical-uid');
    $draft = new Owner(draft: true, canonical: $canonical, uid: 'authorized-draft');
    $normalized = $field->normalizeValueFromRequest(['add' => ['4']], $draft);
    $draft->setFieldValue('relations', $normalized);
    assertSame(true, $field->beforeElementSave($draft, false), 'An authorized draft author must be able to save relationship changes.');
    assertSame([4], $field->serializeValue($normalized, $draft), 'Public field serialization must remain a flat list of IDs.');
    $authorizedValue = $field->serializeValueForDb($normalized, $draft);
    assertSame([4], $authorizedValue['ids'] ?? null, 'Draft database serialization must preserve the selected IDs.');
    assertSame(true, is_string($authorizedValue['authorization'] ?? null), 'Draft database serialization must include authenticated provenance.');

    $field = createField();
    $reloadedDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'authorized-draft');
    $normalized = $field->normalizeValue($authorizedValue, $reloadedDraft);
    $reloadedDraft->setFieldValue('relations', $normalized);
    assertSame($authorizedValue, $field->serializeValueForDb($normalized, $reloadedDraft), 'An unchanged signed draft selection must retain its original provenance.');

    $user->identity = $publisher;
    assertSame(true, $field->prepareDraftApplication($reloadedDraft), 'Publication must authorize the change as the draft selection author, not the publisher.');
    $publishedCanonical = new Owner(dirty: true, fieldValue: $normalized, uid: 'canonical-uid');
    $publishedCanonical->duplicateOf = $reloadedDraft;
    assertSame(true, $field->beforeElementSave($publishedCanonical, false), 'A validated published draft application must hand its prepared changes to the canonical save.');
    $field->afterElementSave($publishedCanonical, false);
    assertSame([99], $originOnly->relationIds, 'A validated published draft application must write the inverse relationship.');

    $localizedCanonical = new Owner(dirty: true, fieldValue: $normalized, uid: 'canonical-uid');
    $localizedCanonical->siteId = 2;
    $localizedCanonical->duplicateOf = $reloadedDraft;
    assertSame(true, $field->beforeElementSave($localizedCanonical, false), 'A second localized clone must be able to reuse the validated draft application.');
    $field->afterElementSave($localizedCanonical, false);
    assertSame([99], $originOnly->relationIds, 'Repeated localized inverse writes must remain idempotent.');

    $user->identity = $originUser;
    $adapter->relatedElements = [$originOnly];
    $field = createField();
    $removalDraft = new Owner(draft: true, canonical: $canonical, uid: 'removal-draft');
    $normalized = $field->normalizeValueFromRequest(['add' => []], $removalDraft);
    $removalDraft->setFieldValue('relations', $normalized);
    assertSame(true, $field->beforeElementSave($removalDraft, false), 'An authorized draft author must be able to save a relationship removal.');
    $removalValue = $field->serializeValueForDb($normalized, $removalDraft);
    assertSame([], $removalValue['ids'] ?? null, 'An authorized empty selection must retain authenticated provenance.');

    $field = createField();
    $removalReload = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'removal-draft');
    $normalized = $field->normalizeValue($removalValue, $removalReload);
    $removalReload->setFieldValue('relations', $normalized);
    $user->identity = $publisher;
    assertSame(true, $field->prepareDraftApplication($removalReload), 'A relationship removal must be reauthorized as its draft author at publication.');
    $publishedCanonical = new Owner(dirty: true, fieldValue: $normalized, uid: 'canonical-uid');
    $publishedCanonical->duplicateOf = $removalReload;
    assertSame(true, $field->beforeElementSave($publishedCanonical, false), 'A validated removal must hand its prepared change to the canonical save.');
    $field->afterElementSave($publishedCanonical, false);
    assertSame([], $originOnly->relationIds, 'A validated published draft application must remove the inverse relationship.');
    $adapter->relatedElements = [];

    $originOnly->relationIds = [];
    $user->identity = $originUser;
    $field = createField();
    $collaborativeDraft = new Owner(draft: true, canonical: $canonical, uid: 'collaborative-draft');
    $normalized = $field->normalizeValueFromRequest(['add' => ['4']], $collaborativeDraft);
    $collaborativeDraft->setFieldValue('relations', $normalized);
    assertSame(true, $field->beforeElementSave($collaborativeDraft, false), 'The original authorized collaborator must be able to save a selection.');
    $collaborativeValue = $field->serializeValueForDb($normalized, $collaborativeDraft);

    $field = createField();
    $collaborativeReload = new Owner(draft: true, canonical: $canonical, uid: 'collaborative-draft');
    $normalized = $field->normalizeValue($collaborativeValue, $collaborativeReload);
    $collaborativeReload->setFieldValue('relations', $normalized);
    $user->identity = $publisher;
    $normalized = $field->normalizeValueFromRequest(['add' => ['4']], $collaborativeReload);
    $collaborativeReload->setFieldValue('relations', $normalized);
    assertSame(true, $field->beforeElementSave($collaborativeReload, false), 'An unchanged selection must keep the originating collaborator authorization.');
    assertSame($collaborativeValue, $field->serializeValueForDb($normalized, $collaborativeReload), 'A later collaborator must not replace unchanged selection provenance.');

    $originOnly->saveableUserIds = [20];
    $field = createField();
    $revokedDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'collaborative-draft');
    $normalized = $field->normalizeValue($collaborativeValue, $revokedDraft);
    $revokedDraft->setFieldValue('relations', $normalized);
    assertSame(false, $field->prepareDraftApplication($revokedDraft), 'A publisher must not substitute their authority after the origin author loses permission.');

    $originOnly->saveableUserIds = [10];
    $users->users[10] = new User(10, 'suspended');
    $field = createField();
    $inactiveDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'collaborative-draft');
    $normalized = $field->normalizeValue($collaborativeValue, $inactiveDraft);
    $inactiveDraft->setFieldValue('relations', $normalized);
    assertSame(false, $field->prepareDraftApplication($inactiveDraft), 'A suspended origin author must not retain draft-application authority.');
    $users->users[10] = $originUser;

    $tamperedValue = $authorizedValue;
    $tamperedValue['ids'] = [];
    $field = createField();
    $tamperedDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'authorized-draft');
    $normalized = $field->normalizeValue($tamperedValue, $tamperedDraft);
    $tamperedDraft->setFieldValue('relations', $normalized);
    assertSame(false, $field->prepareDraftApplication($tamperedDraft), 'Changing signed draft IDs must invalidate their provenance.');

    $malformedValue = $authorizedValue + ['unexpected' => true];
    $field = createField();
    $malformedDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'authorized-draft');
    $normalized = $field->normalizeValue($malformedValue, $malformedDraft);
    $malformedDraft->setFieldValue('relations', $normalized);
    assertSame([4], elementIds($normalized), 'A malformed authorization envelope must preserve its selected relationship IDs for recovery.');
    assertSame(false, $field->prepareDraftApplication($malformedDraft), 'A malformed draft authorization envelope must be rejected.');

    $reorderedValue = [
        'authorization' => $authorizedValue['authorization'],
        'ids' => $authorizedValue['ids'],
    ];
    $field = createField();
    $reorderedDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'authorized-draft');
    $normalized = $field->normalizeValue($reorderedValue, $reorderedDraft);
    $reorderedDraft->setFieldValue('relations', $normalized);
    assertSame(true, $field->prepareDraftApplication($reorderedDraft), 'Authorization envelope key order must not affect validation.');

    $field = createField();
    $copiedDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'copied-draft');
    $normalized = $field->normalizeValue($authorizedValue, $copiedDraft);
    $copiedDraft->setFieldValue('relations', $normalized);
    assertSame(false, $field->prepareDraftApplication($copiedDraft), 'Copying signed provenance to another draft must not authorize it.');

    $field = createField();
    $legacyDraft = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'legacy-draft');
    $normalized = $field->normalizeValue([4], $legacyDraft);
    $legacyDraft->setFieldValue('relations', $normalized);
    assertSame(false, $field->prepareDraftApplication($legacyDraft), 'A modified legacy draft without authenticated provenance must be rejected.');

    $user->identity = $originUser;
    $normalized = $field->normalizeValueFromRequest(['add' => ['4']], $legacyDraft);
    $legacyDraft->setFieldValue('relations', $normalized);
    assertSame(true, $field->beforeElementSave($legacyDraft, false), 'An authorized resave must upgrade a legacy draft selection.');
    $upgradedValue = $field->serializeValueForDb($normalized, $legacyDraft);
    assertSame(true, is_string($upgradedValue['authorization'] ?? null), 'An authorized legacy draft resave must add authenticated provenance.');

    $field = createField();
    $nestedDraft = new Owner(draft: true, canonical: $canonical, uid: 'nested-draft');
    $normalized = $field->normalizeValueFromRequest(['add' => ['4']], $nestedDraft);
    $nestedDraft->setFieldValue('relations', $normalized);
    assertSame(true, $field->beforeElementSave($nestedDraft, false), 'An authorized nested draft selection must save.');
    $nestedValue = $field->serializeValueForDb($normalized, $nestedDraft);

    $field = createField();
    $nestedReload = new Owner(draft: true, dirty: true, canonical: $canonical, uid: 'nested-draft');
    $normalized = $field->normalizeValue($nestedValue, $nestedReload);
    $nestedReload->setFieldValue('relations', $normalized);
    $nestedCanonical = new Owner(dirty: true, fieldValue: $normalized, uid: 'nested-canonical');
    $nestedCanonical->duplicateOf = $nestedReload;
    assertSame(true, $field->beforeElementSave($nestedCanonical, false), 'A nested draft clone must revalidate its own signed origin when no top-level draft event visited it.');
    $field->afterElementSave($nestedCanonical, false);
    assertSame([99], $originOnly->relationIds, 'A directly validated nested draft clone must write the inverse relationship.');

    $field = createField();
    $unpublishedDraft = new Owner(draft: true, uid: 'unpublished-draft');
    $normalized = $field->normalizeValueFromRequest(['add' => ['4']], $unpublishedDraft);
    $unpublishedDraft->setFieldValue('relations', $normalized);
    assertSame(true, $field->beforeElementSave($unpublishedDraft, true), 'An authorized unpublished draft selection must save.');
    $unpublishedValue = $field->serializeValueForDb($normalized, $unpublishedDraft);

    $field = createField();
    $unpublishedReload = new Owner(draft: true, dirty: true, uid: 'unpublished-draft');
    $normalized = $field->normalizeValue($unpublishedValue, $unpublishedReload);
    $unpublishedReload->setFieldValue('relations', $normalized);
    assertSame(true, $field->prepareDraftApplication($unpublishedReload), 'An unpublished draft application must validate before Craft removes its draft state.');
    $unpublishedReload->draft = false;
    assertSame(true, $field->beforeElementSave($unpublishedReload, true), 'An unpublished draft must retain its validated handoff after draft state is removed.');
    $field->afterElementSave($unpublishedReload, true);
    assertSame([99], $originOnly->relationIds, 'A validated unpublished draft application must write the inverse relationship.');

    echo "Many to Many field view authorization security fixture passed.\n";
}
