<?php

declare(strict_types=1);

namespace EventManagerKit\Tests\Dashboard;

use ReflectionClass;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use EventManagerKit\Entity\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Concrete\Core\Error\ErrorList\ErrorList;
use Symfony\Component\HttpFoundation\Request;
use EventManagerKit\Dashboard\NamedEntityPage;
use Concrete\Core\Http\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * persist()/create() are what the event form's "+ New" popup posts to, so they
 * must enforce exactly the rules of the regular add page.
 */
final class NamedEntityPageTest extends TestCase
{
    private const DEFAULT_LOCALE = 'en_GB';

    /** @var list<object> */
    private array $persisted = [];

    private function page(bool $validToken = true, ?object $existing = null, bool $isPost = true, array $post = []): object
    {
        $repo = $this->createMock(ObjectRepository::class);
        $repo->method('findOneBy')->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $em->method('find')->willReturn(null);
        $em->method('persist')->willReturnCallback(function ($e) {
            // Doctrine assigns the GUID on insert; mimic it.
            (new ReflectionProperty($e, 'id'))->setValue($e, 'new-id-' . count($this->persisted));
            $this->persisted[] = $e;
        });

        $token = new class($validToken) {
            public function __construct(private bool $ok) {}

            public function validate(string $key): bool
            {
                return $this->ok;
            }

            public function getErrorMessage(): string
            {
                return 'Invalid token';
            }
        };

        $text = new class {
            public function handle(string $s): string
            {
                return trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($s)), '_');
            }
        };
        $app = new class($text) {
            public function __construct(private object $text) {}

            public function make(string $abstract): mixed
            {
                return $this->text;
            }
        };

        // Only json() is used; a bare stub keeps the test free of the CMS boot.
        $json = new class {
            public function json(mixed $data): JsonResponse
            {
                return new JsonResponse($data);
            }
        };

        // Instantiate without the CMS-bound constructor, then inject collaborators.
        $page = (new ReflectionClass(TestableTypePage::class))->newInstanceWithoutConstructor();
        $page->wire($em, $token, new ErrorList(), $app, $json, Request::create('/', $isPost ? 'POST' : 'GET', $post));

        return $page;
    }

    public function testRejectsMissingNames(): void
    {
        $page = $this->page();

        $this->assertNull($page->persist(['name' => ['en_GB' => '  ']]));
        $this->assertTrue($page->errors()->has());
        $this->assertSame([], $this->persisted);
    }

    public function testRejectsInvalidToken(): void
    {
        $page = $this->page(validToken: false);

        $this->assertNull($page->persist(['name' => ['en_GB' => 'Workshop']]));
        $this->assertContains('Invalid token', array_map('strval', $page->errors()->getList()));
    }

    public function testRejectsDuplicateHandle(): void
    {
        $page = $this->page(existing: new EventType());

        $this->assertNull($page->persist(['name' => ['en_GB' => 'Workshop']]));
        $this->assertStringContainsString('already exists', (string) $page->errors()->getList()[0]);
    }

    public function testNewRecordNeedsTheDefaultLocaleName(): void
    {
        $page = $this->page();

        $this->assertNull($page->persist(['name' => ['cy_GB' => 'Gweithdy']]));
        $this->assertStringContainsString(self::DEFAULT_LOCALE, (string) $page->errors()->getList()[0]);
    }

    public function testSavesWithHandleFromDefaultLocaleName(): void
    {
        $page = $this->page();

        $saved = $page->persist(['name' => ['en_GB' => ' Open Day ']]);

        $this->assertInstanceOf(EventType::class, $saved);
        $this->assertSame('open_day', $saved->getHandle());
        $this->assertSame('Open Day', $saved->getName('en_GB'));
        $this->assertSame([$saved], $this->persisted);
        $this->assertFalse($page->errors()->has());
    }

    public function testCreateReturnsIdAndLabelAsJson(): void
    {
        $page = $this->page(post: ['name' => ['en_GB' => 'Workshop']]);

        $data = json_decode($page->create()->getContent(), true);

        $this->assertSame($this->persisted[0]->getID(), $data['id']);
        $this->assertSame('Workshop', $data['label']);
    }

    public function testCreateReturnsErrorsAsJsonAndSavesNothing(): void
    {
        $page = $this->page(post: ['name' => []]);

        $data = json_decode($page->create()->getContent(), true);

        $this->assertTrue($data['error']);
        $this->assertNotEmpty($data['errors']);
        $this->assertSame([], $this->persisted);
    }

    public function testCreateIgnoresGetRequests(): void
    {
        $page = $this->page(isPost: false, post: ['name' => ['en_GB' => 'Workshop']]);

        $page->create();

        $this->assertSame([], $this->persisted);
    }
}

/** Event-type page with its protected hooks opened up and CMS collaborators injectable. */
class TestableTypePage extends NamedEntityPage
{
    protected function entityClass(): string
    {
        return EventType::class;
    }

    protected function basePath(): string
    {
        return '/dashboard/event_manager/event_types';
    }

    protected function searchControllerClass(): string
    {
        return '';
    }

    protected function label(): string
    {
        return 'Event Type';
    }

    protected function pluralLabel(): string
    {
        return 'Event Types';
    }

    public function wire($em, $token, $error, $app, object $rf, Request $request): void
    {
        $this->em = $em;
        $this->token = $token;
        $this->error = $error;
        $this->app = new class($app, $rf) {
            public function __construct(private object $inner, private object $rf) {}

            public function make(string $abstract): mixed
            {
                return $abstract === ResponseFactoryInterface::class ? $this->rf : $this->inner->make($abstract);
            }
        };
        $this->request = new class($request) {
            public object $request;

            public function __construct(private Request $r)
            {
                $this->request = $r->request;
            }

            public function isPost(): bool
            {
                return $this->r->isMethod('POST');
            }
        };
    }

    public function persist(array $post, &$entity = null)
    {
        return parent::persist($post, $entity);
    }

    public function create()
    {
        return parent::create();
    }

    public function errors(): ErrorList
    {
        return $this->error;
    }
}
