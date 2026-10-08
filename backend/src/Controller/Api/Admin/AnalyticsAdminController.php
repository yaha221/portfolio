<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\Request\AnalyticsQuery;
use App\Dto\Response\Admin\AnalyticsView;
use App\Dto\Response\Admin\DailyStat;
use App\Dto\Response\Admin\PathStat;
use App\OpenApi\ProblemResponse;
use App\Repository\PageViewRepository;
use DateTimeImmutable;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin: analytics')]
#[Security(name: 'Bearer')]
final class AnalyticsAdminController extends AbstractController
{
    private const int TOP_PATHS = 10;

    #[Route('/api/admin/analytics', name: 'api_admin_analytics', methods: ['GET'], format: 'json')]
    #[OA\Get(summary: 'Просмотры и посетители по дням', description: 'Дни по UTC.')]
    #[OA\Response(response: 200, description: 'Статистика за период', content: new OA\JsonContent(ref: new Model(type: AnalyticsView::class)))]
    #[ProblemResponse(400, 'Неверный период')]
    #[ProblemResponse(401, 'Нет access-токена или он истёк')]
    #[ProblemResponse(403, 'Нет роли ROLE_ADMIN')]
    public function __invoke(
        PageViewRepository $pageViews,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        AnalyticsQuery $query = new AnalyticsQuery(),
    ): JsonResponse {
        $to = new DateTimeImmutable('today');
        $from = $to->modify(\sprintf('-%d days', $query->days - 1));

        $days = array_map(
            static fn (array $row) => new DailyStat($row['day'], $row['views'], $row['visitors']),
            $pageViews->dailyStats($from, $to),
        );
        $topPaths = array_map(
            static fn (array $row) => new PathStat($row['path'], $row['views']),
            $pageViews->topPaths($from, self::TOP_PATHS),
        );

        return $this->json(new AnalyticsView(
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
            array_sum(array_column($days, 'views')),
            array_sum(array_column($days, 'visitors')),
            $days,
            $topPaths,
        ));
    }
}
