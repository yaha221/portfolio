<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\Request\Admin\ProjectPatchRequest;
use App\Dto\Request\Admin\ProjectRequest;
use App\Dto\Response\Admin\AdminProjectView;
use App\Entity\Project;
use App\OpenApi\ItemsResponse;
use App\OpenApi\ProblemResponse;
use App\Repository\ProjectRepository;
use App\Service\Admin\ProjectWriter;
use App\Service\UploadStorage;
use App\Service\UploadUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/projects', name: 'api_admin_projects_', format: 'json')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin: projects')]
#[Security(name: 'Bearer')]
#[ProblemResponse(401, 'Нет access-токена или он истёк')]
#[ProblemResponse(403, 'Нет роли ROLE_ADMIN')]
final class ProjectAdminController extends AbstractController
{
    public function __construct(
        private readonly ProjectWriter $writer,
        private readonly UploadStorage $storage,
        private readonly UploadUrlGenerator $uploads,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Все проекты, включая черновики')]
    #[ItemsResponse(AdminProjectView::class, 'Проекты по sortOrder')]
    public function list(ProjectRepository $projects): JsonResponse
    {
        return $this->json(['items' => array_map($this->view(...), $projects->findAllForAdmin())]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => Requirement::DIGITS], methods: ['GET'])]
    #[OA\Get(summary: 'Проект')]
    #[OA\Response(response: 200, description: 'Проект', content: new OA\JsonContent(ref: new Model(type: AdminProjectView::class)))]
    #[ProblemResponse(404, 'Проект не найден')]
    public function show(Project $project): JsonResponse
    {
        return $this->json($this->view($project));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(summary: 'Создать проект')]
    #[OA\Response(
        response: 201,
        description: 'Проект создан',
        headers: [new OA\Header(header: 'Location', description: 'URL проекта в админском API', schema: new OA\Schema(type: 'string'))],
        content: new OA\JsonContent(ref: new Model(type: AdminProjectView::class)),
    )]
    #[ProblemResponse(422, 'Ошибки валидации: поля, занятый slug, неизвестная технология')]
    public function create(#[MapRequestPayload] ProjectRequest $request): JsonResponse
    {
        $project = $this->writer->create($request);

        return $this->json($this->view($project), Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_admin_projects_show', ['id' => $project->getId()]),
        ]);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[OA\Put(summary: 'Заменить данные проекта', description: 'Картинки и обложка меняются отдельными запросами.')]
    #[OA\Response(response: 200, description: 'Проект обновлён', content: new OA\JsonContent(ref: new Model(type: AdminProjectView::class)))]
    #[ProblemResponse(404, 'Проект не найден')]
    #[ProblemResponse(422, 'Ошибки валидации')]
    public function update(Project $project, #[MapRequestPayload] ProjectRequest $request): JsonResponse
    {
        $this->writer->update($project, $request);

        return $this->json($this->view($project));
    }

    #[Route('/{id}', name: 'patch', requirements: ['id' => Requirement::DIGITS], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Опубликовать, снять с публикации или переставить', description: 'Поля, которых нет в запросе, не меняются.')]
    #[OA\Response(response: 200, description: 'Проект обновлён', content: new OA\JsonContent(ref: new Model(type: AdminProjectView::class)))]
    #[ProblemResponse(404, 'Проект не найден')]
    #[ProblemResponse(422, 'Ошибки валидации')]
    public function patch(Project $project, #[MapRequestPayload] ProjectPatchRequest $request): JsonResponse
    {
        $this->writer->patch($project, $request);

        return $this->json($this->view($project));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Удалить проект вместе с картинками')]
    #[OA\Response(response: 204, description: 'Проект удалён')]
    #[ProblemResponse(404, 'Проект не найден')]
    public function delete(Project $project): Response
    {
        $files = $project->getUploadedFiles();

        $this->entityManager->remove($project);
        $this->entityManager->flush();
        // After the commit: a failed delete must not leave a project pointing at missing files
        $this->storage->delete($files);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function view(Project $project): AdminProjectView
    {
        return AdminProjectView::fromEntity($project, $this->uploads);
    }
}
