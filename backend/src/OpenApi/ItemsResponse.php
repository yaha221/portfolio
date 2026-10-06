<?php

declare(strict_types=1);

namespace App\OpenApi;

use Attribute;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * Documents a 200 response shaped as {"items": [Model, ...]}: #[ItemsResponse(AdminSkillView::class, 'Навыки')].
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ItemsResponse extends OA\Response
{
    /**
     * @param class-string $itemClass
     */
    public function __construct(string $itemClass, string $description)
    {
        parent::__construct(
            response: 200,
            description: $description,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: $itemClass))),
                ],
            ),
        );
    }
}
