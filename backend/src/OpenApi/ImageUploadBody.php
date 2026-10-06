<?php

declare(strict_types=1);

namespace App\OpenApi;

use Attribute;
use OpenApi\Attributes as OA;

/**
 * Documents a multipart/form-data body with one image in the "file" field.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ImageUploadBody extends OA\RequestBody
{
    public function __construct()
    {
        parent::__construct(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [new OA\Property(property: 'file', description: 'JPEG, PNG или WebP до 10 МБ', type: 'string', format: 'binary')],
                ),
            ),
        );
    }
}
