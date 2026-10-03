<?php

declare(strict_types=1);

namespace craft\base {
    interface ElementInterface
    {
    }

    class Plugin
    {
        public function init(): void
        {
        }
    }
}

namespace craft\errors {
    use craft\base\ElementInterface;

    class InvalidElementException extends \RuntimeException
    {
        public function __construct(public ElementInterface $element, ?string $message = null)
        {
            parent::__construct($message ?? 'Invalid element.');
        }
    }
}

namespace craft\events {
    use craft\base\ElementInterface;

    class DraftEvent
    {
        public ElementInterface $canonical;
        public ?ElementInterface $draft = null;
    }

    class RegisterComponentTypesEvent
    {
        public array $types = [];
    }
}

namespace craft\services {
    class Drafts
    {
        public const EVENT_BEFORE_APPLY_DRAFT = 'beforeApplyDraft';
    }

    class Fields
    {
        public const EVENT_REGISTER_FIELD_TYPES = 'registerFieldTypes';
    }
}

namespace yii\base {
    class Event
    {
        public static array $handlers = [];

        public static function on(string $class, string $event, callable $handler): void
        {
            self::$handlers[$class][$event][] = $handler;
        }
    }
}

namespace verbb\manytomany\base {
    trait PluginTrait
    {
        public static object $plugin;
    }
}

namespace verbb\manytomany\fields {
    use craft\base\ElementInterface;

    class ManyToManyField
    {
        public int $prepareCalls = 0;

        public function __construct(
            public string $handle,
            public array $ids,
            public bool $authorized = true,
        ) {
        }

        public function prepareDraftApplication(ElementInterface $draft): bool
        {
            $this->prepareCalls++;

            return $this->authorized;
        }

        public function serializeValue(mixed $value, ?ElementInterface $element): array
        {
            return $this->ids;
        }
    }
}

namespace Fixture {
    use craft\base\ElementInterface;

    class Draft implements ElementInterface
    {
        public function __construct(
            private FieldLayout $layout,
            private bool $modified,
        ) {
        }

        public function getFieldLayout(): FieldLayout
        {
            return $this->layout;
        }

        public function getFieldValue(string $handle): array
        {
            return [];
        }

        public function isFieldModified(string $handle, bool $anySite = false): bool
        {
            return $this->modified;
        }
    }

    class FieldLayout
    {
        public function __construct(private array $fields)
        {
        }

        public function getCustomFields(): array
        {
            return $this->fields;
        }
    }
}

namespace {
    use craft\errors\InvalidElementException;
    use craft\events\DraftEvent;
    use craft\services\Drafts;
    use Fixture\Draft;
    use Fixture\FieldLayout;
    use verbb\manytomany\fields\ManyToManyField;
    use yii\base\Event;

    class Craft
    {
        public static function t(string $category, string $message): string
        {
            return $message;
        }
    }

    require dirname(__DIR__, 2) . '/src/ManyToMany.php';

    function assertSame(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message . '\nExpected: ' . var_export($expected, true) . '\nActual: ' . var_export($actual, true));
        }
    }

    $plugin = new \verbb\manytomany\ManyToMany();
    $plugin->init();
    $handler = Event::$handlers[Drafts::class][Drafts::EVENT_BEFORE_APPLY_DRAFT][0] ?? null;
    assertSame(true, is_callable($handler), 'The plugin must register a common draft-application authorization gate.');

    $field = new ManyToManyField('relations', [1]);
    $draft = new Draft(new FieldLayout([$field]), true);
    $canonical = new Draft(new FieldLayout([]), false);
    $event = new DraftEvent();
    $event->draft = $draft;
    $event->canonical = $canonical;
    $handler($event);
    assertSame(1, $field->prepareCalls, 'A modified published-draft field must be authorized before application.');

    $field = new ManyToManyField('relations', [1]);
    $draft = new Draft(new FieldLayout([$field]), false);
    $event = new DraftEvent();
    $event->draft = $draft;
    $event->canonical = $draft;
    $handler($event);
    assertSame(1, $field->prepareCalls, 'A non-empty unpublished-draft field must be authorized before draft state is removed.');

    $field = new ManyToManyField('relations', []);
    $draft = new Draft(new FieldLayout([$field]), false);
    $event = new DraftEvent();
    $event->draft = $draft;
    $event->canonical = $draft;
    $handler($event);
    assertSame(0, $field->prepareCalls, 'An unchanged empty unpublished-draft field must not require provenance.');

    $field = new ManyToManyField('relations', [1], false);
    $draft = new Draft(new FieldLayout([$field]), true);
    $event = new DraftEvent();
    $event->draft = $draft;
    $event->canonical = $canonical;

    try {
        $handler($event);
        throw new RuntimeException('An unauthorized draft application was not rejected.');
    } catch (InvalidElementException $exception) {
        assertSame($draft, $exception->element, 'The rejected draft must remain attached to the validation exception.');
    }

    echo "Many to Many draft application event security fixture passed.\n";
}
