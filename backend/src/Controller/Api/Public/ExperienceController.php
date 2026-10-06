<?php

declare(strict_types=1);

namespace App\Controller\Api\Public;

use App\Dto\Response\ExperienceCollection;
use App\Dto\Response\ExperienceView;
use App\Repository\ExperienceRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Experience')]
final class ExperienceController extends AbstractController
{
    #[Route('/api/experience', name: 'api_experience_list', methods: ['GET'], format: 'json')]
    #[OA\Get(summary: 'Опыт работы', description: 'Сначала самые свежие места работы.')]
    #[OA\Response(response: 200, description: 'Опыт работы', content: new OA\JsonContent(ref: new Model(type: ExperienceCollection::class)))]
    public function list(ExperienceRepository $experience): JsonResponse
    {
        return $this->json(new ExperienceCollection(array_map(ExperienceView::fromEntity(...), $experience->findAllOrdered())));
    }
}
