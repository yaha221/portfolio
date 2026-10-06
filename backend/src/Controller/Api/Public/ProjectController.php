<?php

declare(strict_types=1);

namespace App\Controller\Api\Public;

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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[Route('/api/projects', name: 'api_projects_', format: 'json')]
#[OA\Tag(name: 'Projects')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly UploadUrlGenerator $uploads,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Опубликованные проекты', description: 'Отсортированы по sortOrder. Черновики не возвращаются.')]
    #[OA\Response(response: 200, description: 'Страница проектов', content: new OA\JsonContent(ref: new Model(type: ProjectPage::class)))]
    #[ProblemResponse(400, 'Неверные параметры запроса')]
    public function list(
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        ProjectListQuery $query = new ProjectListQuery(),
    ): JsonResponse {
        $paginator = $this->projects->findPublishedPage($query->technology, $query->page, $query->perPage);

        $items = [];
        foreach ($paginator as $project) {
            $items[] = ProjectListItem::fromEntity($project, $this->uploads);
        }

        return $this->json(new ProjectPage($items, PaginationMeta::of($query->page, $query->perPage, \count($paginator))));
    }

    #[Route('/{slug}', name: 'show', requirements: ['slug' => Requirement::ASCII_SLUG], methods: ['GET'])]
    #[OA\Get(summary: 'Проект по slug')]
    #[OA\Response(response: 200, description: 'Проект', content: new OA\JsonContent(ref: new Model(type: ProjectDetail::class)))]
    #[ProblemResponse(404, 'Проект не найден или не опубликован')]
    public function show(string $slug): JsonResponse
    {
        $project = $this->projects->findPublishedBySlug($slug)
            ?? throw $this->createNotFoundException(\sprintf('Project "%s" not found.', $slug));

        return $this->json(ProjectDetail::fromEntity($project, $this->uploads));
    }
}
