<?php

declare(strict_types=1);

namespace App\Tests\Unit\Cache;

use App\Cache\PublicApiCache;
use App\Dto\Response\TechnologyRef;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class PublicApiCacheTest extends TestCase
{
    private TagAwareAdapter $pool;

    private int $builds = 0;

    protected function setUp(): void
    {
        $this->pool = new TagAwareAdapter(new ArrayAdapter());
    }

    public function testBuildsTheResponseOnceAndThenServesItFromTheCache(): void
    {
        $first = $this->respond(Request::create('/api/technologies'));
        $second = $this->respond(Request::create('/api/technologies'));

        self::assertSame(1, $this->builds);
        self::assertSame($first->getContent(), $second->getContent());
    }

    public function testRebuildsAfterItsTagIsInvalidated(): void
    {
        $this->respond(Request::create('/api/technologies'));

        $this->pool->invalidateTags(['technologies']);
        $this->respond(Request::create('/api/technologies'));

        self::assertSame(2, $this->builds);
    }

    public function testOtherTagsDoNotTouchTheEntry(): void
    {
        $this->respond(Request::create('/api/technologies'));

        $this->pool->invalidateTags(['projects']);
        $this->respond(Request::create('/api/technologies'));

        self::assertSame(1, $this->builds);
    }

    public function testKeepsCyrillicAndSlashesReadable(): void
    {
        $response = $this->respond(Request::create('/api/technologies'));

        self::assertSame('{"slug":"qa","name":"Тестирование/QA","icon":null}', $response->getContent());
    }

    public function testLetsBrowsersKeepTheResponseBrieflyAndRevalidateByEtag(): void
    {
        $response = $this->respond(Request::create('/api/technologies'));

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->hasCacheControlDirective('public'));
        self::assertSame('60', $response->headers->getCacheControlDirective('max-age'));
        self::assertMatchesRegularExpression('/^"[0-9a-f]{32}"$/', (string) $response->getEtag());
    }

    public function testAnswersNotModifiedWhenTheClientHasTheSameVersion(): void
    {
        $etag = (string) $this->respond(Request::create('/api/technologies'))->getEtag();

        $response = $this->respond(Request::create('/api/technologies', server: ['HTTP_IF_NONE_MATCH' => $etag]));

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('', $response->getContent());
    }

    public function testSendsTheBodyWhenTheClientHasAnOlderVersion(): void
    {
        $response = $this->respond(Request::create('/api/technologies', server: ['HTTP_IF_NONE_MATCH' => '"outdated"']));

        self::assertSame(200, $response->getStatusCode());
        self::assertNotSame('', $response->getContent());
    }

    private function respond(Request $request): Response
    {
        $cache = new PublicApiCache($this->pool, new Serializer([new ObjectNormalizer()], [new JsonEncoder()]));

        return $cache->respond($request, 'technologies.list', ['technologies'], function (): TechnologyRef {
            ++$this->builds;

            return new TechnologyRef('qa', 'Тестирование/QA', null);
        });
    }
}
