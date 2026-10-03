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
        public function __construct(
            public int $id,
            public bool $viewable,
            public bool $saveable = true,
        ) {
        }

        public function getFieldLayout(): FakeFieldLayout
        {
            return new FakeFieldLayout();
        }
    }

    class Owner implements ElementInterface
    {
        public array $errors = [];

        public function __construct(
            private bool $draft = false,
            private bool $revision = false,
            private bool $dirty = false,
            private array $fieldValue = [],
        ) {
        }

        public function addError(string $attribute, string $message): void
        {
            $this->errors[$attribute][] = $message;
        }

        public function getCanonicalId(): int
        {
            return 99;
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
    }

    class FakeApp
    {
        public function __construct(
            private FakeUserComponent $user,
            private FakeElementsService $elements,
            private FakeFieldsService $fields,
            private FakeView $view,
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

        public function getView(): FakeView
        {
            return $this->view;
        }
    }

    class FakeElementsService
    {
        public function canSave(LinkedElement $element, object $user): bool
        {
            return $element->saveable;
        }

        public function canView(LinkedElement $element, object $user): bool
        {
            return $element->viewable;
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
    use craft\helpers\Cp;
    use Fixture\FakeApp;
    use Fixture\FakeElementsService;
    use Fixture\FakeFieldsService;
    use Fixture\FakeUserComponent;
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

    $user = new FakeUserComponent(new stdClass());
    $view = new FakeView();
    Craft::$app = new FakeApp($user, new FakeElementsService(), new FakeFieldsService(), $view);

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
    $field = new TestManyToManyField([
        'handle' => 'relations',
        'singleField' => 'associated-field',
        'source' => ['type' => 'section', 'value' => 'source-uid'],
    ]);
    $draft = new Owner(draft: true);
    assertSame([], $field->normalizeValueFromRequest(['add' => []], $draft), 'An empty selection must remain empty without an authenticated identity.');
    assertSame(true, $field->beforeElementSave($draft, false), 'An empty selection must remain valid without an authenticated identity.');

    $user->identity = new stdClass();
    $service = new Service();
    $adapter->relatedElements = [$visible, $hidden];
    $changes = $service->prepareRelationshipChanges($field, new Owner(), [1]);
    assertSame([], $changes, 'Omitted existing relations hidden from the editor must remain unchanged.');

    $changes = $service->prepareRelationshipChanges($field, new Owner(), []);
    assertSame([1], array_map(fn(array $change): int => $change['source']->id, $changes ?? []), 'A visible relation must remain removable while a hidden relation is preserved.');

    echo "Many to Many field view authorization security fixture passed.\n";
}
