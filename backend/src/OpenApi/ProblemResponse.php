<?php

declare(strict_types=1);

namespace App\OpenApi;

use App\Dto\Response\ProblemDetails;
use Attribute;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

/**
 * Documents an error response with a problem+json body: #[ProblemResponse(404, 'Проект не найден')].
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class ProblemResponse extends OA\Response
{
    public function __construct(int $status, string $description)
    {
        parent::__construct(
            response: $status,
            description: $description,
            content: new OA\MediaType(
                mediaType: 'application/problem+json',
                schema: new OA\Schema(ref: new Model(type: ProblemDetails::class)),
            ),
        );
    }
}
