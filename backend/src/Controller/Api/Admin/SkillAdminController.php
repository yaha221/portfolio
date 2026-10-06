<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\Request\Admin\SkillRequest;
use App\Dto\Response\Admin\AdminSkillView;
use App\Entity\Skill;
use App\OpenApi\ItemsResponse;
use App\OpenApi\ProblemResponse;
use App\Repository\SkillRepository;
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

#[Route('/api/admin/skills', name: 'api_admin_skills_', format: 'json')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin: skills')]
#[Security(name: 'Bearer')]
#[ProblemResponse(401, 'Нет access-токена или он истёк')]
#[ProblemResponse(403, 'Нет роли ROLE_ADMIN')]
final class SkillAdminController extends AbstractController
{
    public function __construct(
        private readonly SkillRepository $skills,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(summary: 'Все навыки')]
    #[ItemsResponse(AdminSkillView::class, 'Навыки по sortOrder')]
    public function list(): JsonResponse
    {
        return $this->json(['items' => array_map(AdminSkillView::fromEntity(...), $this->skills->findAllOrdered())]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(summary: 'Добавить навык')]
    #[OA\Response(response: 201, description: 'Навык добавлен', content: new OA\JsonContent(ref: new Model(type: AdminSkillView::class)))]
    #[ProblemResponse(422, 'Ошибки валидации или навык с таким именем уже есть')]
    public function create(#[MapRequestPayload] SkillRequest $request): JsonResponse
    {
        $this->assertNameIsFree($request->name, null);

        $skill = new Skill($request->name, $request->category, $request->level);
        $skill->setSortOrder($request->sortOrder);
        $this->entityManager->persist($skill);
        $this->entityManager->flush();

        return $this->json(AdminSkillView::fromEntity($skill), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => Requirement::DIGITS], methods: ['PUT'])]
    #[OA\Put(summary: 'Изменить навык')]
    #[OA\Response(response: 200, description: 'Навык обновлён', content: new OA\JsonContent(ref: new Model(type: AdminSkillView::class)))]
    #[ProblemResponse(404, 'Навык не найден')]
    #[ProblemResponse(422, 'Ошибки валидации или навык с таким именем уже есть')]
    public function update(Skill $skill, #[MapRequestPayload] SkillRequest $request): JsonResponse
    {
        $this->assertNameIsFree($request->name, $skill);

        $skill->setName($request->name);
        $skill->setCategory($request->category);
        $skill->setLevel($request->level);
        $skill->setSortOrder($request->sortOrder);
        $this->entityManager->flush();

        return $this->json(AdminSkillView::fromEntity($skill));
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => Requirement::DIGITS], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Удалить навык')]
    #[OA\Response(response: 204, description: 'Навык удалён')]
    #[ProblemResponse(404, 'Навык не найден')]
    public function delete(Skill $skill): Response
    {
        $this->entityManager->remove($skill);
        $this->entityManager->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    private function assertNameIsFree(string $name, ?Skill $current): void
    {
        $owner = $this->skills->findOneBy(['name' => $name]);
        if (null !== $owner && $owner !== $current) {
            throw InvalidRequest::at('name', 'Another skill already has this name.', $name);
        }
    }
}
