<?php

declare(strict_types=1);

namespace App\Controller\Api\Public;

use App\Cache\CacheTag;
use App\Cache\PublicApiCache;
use App\Dto\Response\TechnologyCollection;
use App\Dto\Response\TechnologyView;
use App\Repository\TechnologyRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Technologies')]
final class TechnologyController extends AbstractController
{
    #[Route('/api/technologies', name: 'api_technologies_list', methods: ['GET'], format: 'json')]
    #[OA\Get(summary: 'Технологии для фильтра проектов', description: 'Только те, что есть хотя бы в одном опубликованном проекте.')]
    #[OA\Response(response: 200, description: 'Технологии по имени', content: new OA\JsonContent(ref: new Model(type: TechnologyCollection::class)))]
    #[OA\Response(response: 304, description: 'Не изменилось: ETag совпал с If-None-Match')]
    public function list(Request $request, TechnologyRepository $technologies, PublicApiCache $cache): Response
    {
        return $cache->respond($request, 'technologies.list', [CacheTag::TECHNOLOGIES], static fn (): TechnologyCollection => new TechnologyCollection(array_map(
            static fn (array $row) => new TechnologyView($row['slug'], $row['name'], $row['icon'], $row['projectCount']),
            $technologies->findUsedByPublishedProjects(),
        )));
    }
}
