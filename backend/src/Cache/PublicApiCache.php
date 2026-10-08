<?php

declare(strict_types=1);

namespace App\Cache;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Serves public GET endpoints from Redis: the JSON is built once and kept until a database
 * write invalidates its tags. Adds HTTP caching on top: ETag + short browser max-age, 304 on a match.
 */
final readonly class PublicApiCache
{
    // Browsers can't be told about changes, so they keep a copy only briefly and then revalidate by ETag
    private const int BROWSER_MAX_AGE = 60;

    // Cyrillic as UTF-8 instead of \uXXXX: about half the size of the default encoding
    private const int JSON_OPTIONS = \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR;

    public function __construct(
        #[Autowire(service: 'cache.public_api')]
        private TagAwareCacheInterface $cache,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @param list<string>       $tags  CacheTag values the response depends on
     * @param callable(): object $build builds the response DTO on a cache miss; may throw HTTP exceptions
     */
    public function respond(Request $request, string $key, array $tags, callable $build): Response
    {
        $json = $this->cache->get($key, function (ItemInterface $item) use ($tags, $build): string {
            $item->tag($tags);

            return $this->serializer->serialize($build(), 'json', ['json_encode_options' => self::JSON_OPTIONS]);
        });

        $response = JsonResponse::fromJsonString($json);
        $response->setPublic();
        $response->setMaxAge(self::BROWSER_MAX_AGE);
        $response->setEtag(hash('xxh128', $json));
        // Turns the response into an empty 304 when the client's If-None-Match matches
        $response->isNotModified($request);

        return $response;
    }
}
