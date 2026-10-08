<?php

declare(strict_types=1);

namespace App\Controller\Api\Public;

use App\Dto\Request\PageViewRequest;
use App\Message\PageViewed;
use App\Messenger\BestEffortDispatcher;
use App\OpenApi\ProblemResponse;
use App\Service\Analytics\VisitorHasher;
use DateTimeImmutable;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Analytics')]
final class PageViewController extends AbstractController
{
    // Crawlers, link previews and audit tools: not visitors
    private const string BOT_PATTERN = '/bot|crawl|spider|slurp|preview|headless|lighthouse|curl|wget|python-requests/i';

    #[Route('/api/page-views', name: 'api_page_views_record', methods: ['POST'], format: 'json')]
    #[OA\Post(
        summary: 'Учесть просмотр страницы SPA',
        description: 'SPA вызывает при каждой смене маршрута. IP не хранится: только хеш IP и User-Agent с солью, которая меняется каждый день. Запросы ботов молча пропускаются.',
    )]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: PageViewRequest::class)))]
    #[OA\Response(response: 202, description: 'Принято')]
    #[ProblemResponse(422, 'Неверный путь')]
    #[ProblemResponse(429, 'Слишком много запросов')]
    public function __invoke(
        #[MapRequestPayload]
        PageViewRequest $request,
        Request $httpRequest,
        RateLimiterFactoryInterface $pageViewsLimiter,
        VisitorHasher $visitorHasher,
        BestEffortDispatcher $dispatcher,
    ): Response {
        $ip = $httpRequest->getClientIp() ?? 'unknown';
        $limit = $pageViewsLimiter->create($ip)->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(max(1, $limit->getRetryAfter()->getTimestamp() - time()), 'Too many page views from this address.');
        }

        $userAgent = $httpRequest->headers->get('User-Agent');
        if (null === $userAgent || '' === $userAgent || 1 === preg_match(self::BOT_PATTERN, $userAgent)) {
            return new Response(null, Response::HTTP_ACCEPTED);
        }

        $now = new DateTimeImmutable();
        $dispatcher->dispatch(new PageViewed($request->path, $visitorHasher->hash($ip, $userAgent, $now), $userAgent, $now));

        return new Response(null, Response::HTTP_ACCEPTED);
    }
}
