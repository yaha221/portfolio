<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\Request\Admin\TechnologyRequest;
use App\Dto\Response\Admin\AdminTechnologyView;
use App\Entity\Technology;
use App\OpenApi\ItemsResponse;
use App\OpenApi\ProblemResponse;
use App\Repository\TechnologyRepository;
use App\Validation\InvalidRequest;
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

#[Route('/api/admin/technologies', name: 'api_admin_technologies_', format: 'json')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin: technologies')]
#[Security(name: 'Bearer')]
#[ProblemResponse(401, 'Нет access-токена или он истёк')]
#[ProblemResponse(403, 'Нет роли ROLE_ADMIN')]
final class TechnologyAdminController extends AbstractController
{
    public function __construct(
        private readonly TechnologyRepository $technologies,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Все технологии')]
    #[ItemsResponse(AdminTechnologyView::class, 'Технологии по имени')]
    public function list(): JsonResponse
    {
        $items = array_map(AdminTechnologyView::fromEntity(...), $this->technologies->findBy([], ['name' => 'ASC']));

        return $this->json(['items' => $items]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(summary: 'Добавить технологию')]
    #[OA\Response(response: 201, description: 'Технология добавлена', content: new OA\JsonContent(ref: new Model(type: AdminTechnologyView::class)))]
    #[ProblemResponse(422, 'Ошибки валидации, занятые имя или slug')]
    public function create(#[MapRequestPayload] TechnologyRequest $request): JsonResponse
    {
        $this->assertUnique($request, null);

        $technology = new Technology($request->name, $request->slug);
        $technology->setIcon($request->icon);
        $this->entityManager->persist($technology);
        $this->entityManager->flush();

        return $this->json(AdminTechnologyView::fromEntity($technology), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[OA\Put(summary: 'Изменить технологию')]
    #[OA\Response(response: 200, description: 'Технология обновлена', content: new OA\JsonContent(ref: new Model(type: AdminTechnologyView::class)))]
    #[ProblemResponse(404, 'Технология не найдена')]
    #[ProblemResponse(422, 'Ошибки валидации, занятые имя или slug')]
    public function update(Technology $technology, #[MapRequestPayload] TechnologyRequest $request): JsonResponse
    {
        $this->assertUnique($request, $technology);

        $technology->setName($request->name);
        $technology->setSlug($request->slug);
        $technology->setIcon($request->icon);
        $this->entityManager->flush();

        return $this->json(AdminTechnologyView::fromEntity($technology));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Удалить технологию', description: 'Она пропадёт из всех проектов.')]
    #[OA\Response(response: 204, description: 'Технология удалена')]
    #[ProblemResponse(404, 'Технология не найдена')]
    public function delete(Technology $technology): Response
    {
        $this->entityManager->remove($technology);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function assertUnique(TechnologyRequest $request, ?Technology $current): void
    {
        foreach (['name' => $request->name, 'slug' => $request->slug] as $field => $value) {
            $owner = $this->technologies->findOneBy([$field => $value]);
            if (null !== $owner && $owner !== $current) {
                throw InvalidRequest::at($field, \sprintf('Another technology already has this %s.', $field), $value);
            }
        }
    }
}
