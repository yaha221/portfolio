<?php

declare(strict_types=1);

namespace App\Controller\Api\Public;

use App\Cache\CacheTag;
use App\Cache\PublicApiCache;
use App\Dto\Response\SkillCollection;
use App\Dto\Response\SkillView;
use App\Repository\SkillRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Skills')]
final class SkillController extends AbstractController
{
    #[Route('/api/skills', name: 'api_skills_list', methods: ['GET'], format: 'json')]
    #[OA\Get(summary: 'Навыки', description: 'В порядке sortOrder; группировку по category делает клиент.')]
    #[OA\Response(response: 200, description: 'Навыки', content: new OA\JsonContent(ref: new Model(type: SkillCollection::class)))]
    #[OA\Response(response: 304, description: 'Не изменилось: ETag совпал с If-None-Match')]
    public function list(Request $request, SkillRepository $skills, PublicApiCache $cache): Response
    {
        return $cache->respond(
            $request,
            'skills.list',
            [CacheTag::SKILLS],
            static fn (): SkillCollection => new SkillCollection(array_map(SkillView::fromEntity(...), $skills->findAllOrdered())),
        );
    }
}
