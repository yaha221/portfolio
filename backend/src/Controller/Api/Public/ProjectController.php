<?php

declare(strict_types=1);

namespace App\Controller\Api\Public;

use App\Cache\CacheTag;
use App\Cache\PublicApiCache;
use App\Dto\Request\ProjectListQuery;
use App\Dto\Response\PaginationMeta;
use App\Dto\Response\ProjectDetail;
use App\Dto\Response\ProjectListItem;
use App\Dto\Response\ProjectPage;
use App\OpenApi\ProblemResponse;
use App\Repository\ProjectRepository;
use App\Service\UploadUrlGenerator;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/projects', name: 'api_projects_', format: 'json')]
#[OA\Tag(name: 'Projects')]
#[OA\Response(response: 304, description: 'Не изменилось: ETag совпал с If-None-Match')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly UploadUrlGenerator $uploads,
        private readonly PublicApiCache $cache,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Опубликованные проекты', description: 'Отсортированы по sortOrder. Черновики не возвращаются.')]
    #[OA\Response(response: 200, description: 'Страница проектов', content: new OA\JsonContent(ref: new Model(type: ProjectPage::class)))]
    #[ProblemResponse(400, 'Неверные параметры запроса')]
    public function list(
        Request $request,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        ProjectListQuery $query = new ProjectListQuery(),
    ): Response {
        // Every part is validated (digits, slug), so the key is safe for the cache
        $key = \sprintf('projects.list.%d.%d.%s', $query->page, $query->perPage, $query->technology ?? 'all');

        return $this->cache->respond($request, $key, [CacheTag::PROJECTS], function () use ($query): ProjectPage {
            $page = $this->projects->findPublishedPage($query->technology, $query->page, $query->perPage);

            return new ProjectPage(
                array_map(fn ($project) => ProjectListItem::fromEntity($project, $this->uploads), $page['items']),
                PaginationMeta::of($query->page, $query->perPage, $page['total']),
            );
        });
    }

    #[Route('/{slug}', name: 'show', requirements: ['slug' => Requirement::ASCII_SLUG], methods: ['GET'])]
    #[OA\Get(summary: 'Проект по slug')]
    #[OA\Response(response: 200, description: 'Проект', content: new OA\JsonContent(ref: new Model(type: ProjectDetail::class)))]
    #[ProblemResponse(404, 'Проект не найден или не опубликован')]
    public function show(Request $request, string $slug): Response
    {
        // A 404 thrown inside is not cached
        return $this->cache->respond($request, 'projects.show.'.$slug, [CacheTag::PROJECTS], function () use ($slug): ProjectDetail {
            $project = $this->projects->findPublishedBySlug($slug)
                ?? throw $this->createNotFoundException(\sprintf('Project "%s" not found.', $slug));

            return ProjectDetail::fromEntity($project, $this->uploads);
        });
    }
}
