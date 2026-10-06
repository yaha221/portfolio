<?php

declare(strict_types=1);

namespace App\Dto\Response\Admin;

use App\Entity\ProjectImage;
use App\Service\UploadUrlGenerator;
use OpenApi\Attributes as OA;

final readonly class AdminProjectImageView
{
    /**
     * @param array<string, string> $thumbnails
     */
    public function __construct(
        public int $id,
        public string $url,
        #[OA\Property(type: 'object', additionalProperties: new OA\AdditionalProperties(type: 'string'))]
        public array $thumbnails,
        public int $position,
    ) {
    }

    public static function fromEntity(ProjectImage $image, UploadUrlGenerator $uploads): self
    {
        return new self(
            (int) $image->getId(),
            $uploads->url($image->getPath()),
            array_map($uploads->url(...), $image->getThumbnails()),
            $image->getPosition(),
        );
    }
}
