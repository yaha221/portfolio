<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\Request\Admin\ExperienceRequest;
use App\Dto\Response\Admin\AdminExperienceView;
use App\Entity\Experience;
use App\OpenApi\ItemsResponse;
use App\OpenApi\ProblemResponse;
use App\Repository\ExperienceRepository;
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

#[Route('/api/admin/experience', name: 'api_admin_experience_', format: 'json')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin: experience')]
#[Security(name: 'Bearer')]
#[ProblemResponse(401, 'Нет access-токена или он истёк')]
#[ProblemResponse(403, 'Нет роли ROLE_ADMIN')]
final class ExperienceAdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Весь опыт работы')]
    #[ItemsResponse(AdminExperienceView::class, 'Текущее место первым, затем по дате окончания')]
    public function list(ExperienceRepository $experience): JsonResponse
    {
        return $this->json(['items' => array_map(AdminExperienceView::fromEntity(...), $experience->findAllOrdered())]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(summary: 'Добавить место работы')]
    #[OA\Response(response: 201, description: 'Запись добавлена', content: new OA\JsonContent(ref: new Model(type: AdminExperienceView::class)))]
    #[ProblemResponse(422, 'Ошибки валидации')]
    public function create(#[MapRequestPayload] ExperienceRequest $request): JsonResponse
    {
        $experience = new Experience($request->company, $request->position, $request->toStartDate());
        $this->apply($experience, $request);
        $this->entityManager->persist($experience);
        $this->entityManager->flush();

        return $this->json(AdminExperienceView::fromEntity($experience), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[OA\Put(summary: 'Изменить место работы')]
    #[OA\Response(response: 200, description: 'Запись обновлена', content: new OA\JsonContent(ref: new Model(type: AdminExperienceView::class)))]
    #[ProblemResponse(404, 'Запись не найдена')]
    #[ProblemResponse(422, 'Ошибки валидации')]
    public function update(Experience $experience, #[MapRequestPayload] ExperienceRequest $request): JsonResponse
    {
        $experience->setCompany($request->company);
        $experience->setPosition($request->position);
        $experience->setStartDate($request->toStartDate());
        $this->apply($experience, $request);
        $this->entityManager->flush();

        return $this->json(AdminExperienceView::fromEntity($experience));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Удалить место работы')]
    #[OA\Response(response: 204, description: 'Запись удалена')]
    #[ProblemResponse(404, 'Запись не найдена')]
    public function delete(Experience $experience): Response
    {
        $this->entityManager->remove($experience);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function apply(Experience $experience, ExperienceRequest $request): void
    {
        $experience->setEndDate($request->toEndDate());
        $experience->setDescription($request->description);
    }
}
