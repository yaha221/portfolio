<?php

declare(strict_types=1);

namespace App\Dto\Response;

use App\Entity\ProjectImage;
use App\Service\UploadUrlGenerator;
use OpenApi\Attributes as OA;

final readonly class ProjectImageView
{
    /**
     * @param array<string, string> $thumbnails
     */
    public function __construct(
        #[OA\Property(example: 'http://localhost:8080/uploads/projects/cover.jpg')]
        public string $url,
        #[OA\Property(
            description: 'Превью: размер => URL. Пусто, пока воркер не обработал картинку',
            type: 'object',
            example: ['small' => 'http://localhost:8080/uploads/projects/cover-small.webp'],
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
        )]
        public array $thumbnails,
    ) {
    }

    public static function fromEntity(ProjectImage $image, UploadUrlGenerator $uploads): self
    {
        return new self(
            $uploads->url($image->getPath()),
            array_map($uploads->url(...), $image->getThumbnails()),
        );
    }
}
