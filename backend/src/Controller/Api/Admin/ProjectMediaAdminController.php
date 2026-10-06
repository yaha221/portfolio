<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\Response\Admin\AdminProjectImageView;
use App\Dto\Response\Admin\AdminProjectView;
use App\Entity\Project;
use App\Entity\ProjectImage;
use App\OpenApi\ImageUploadBody;
use App\OpenApi\ProblemResponse;
use App\Service\UploadStorage;
use App\Service\UploadUrlGenerator;
use App\Validation\InvalidRequest;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Throwable;

/**
 * Project gallery images and cover. Files go to the uploads storage; thumbnails are made later by the worker.
 */
#[Route('/api/admin/projects/{id}', name: 'api_admin_project_media_', requirements: ['id' => Requirement::DIGITS], format: 'json')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin: projects')]
#[Security(name: 'Bearer')]
#[ProblemResponse(401, 'Нет access-токена или он истёк')]
#[ProblemResponse(403, 'Нет роли ROLE_ADMIN')]
#[ProblemResponse(404, 'Проект или картинка не найдены')]
final class ProjectMediaAdminController extends AbstractController
{
    private const string DIRECTORY = 'projects';

    public function __construct(
        private readonly UploadStorage $storage,
        private readonly UploadUrlGenerator $uploads,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/images', name: 'image_add', methods: ['POST'])]
    #[OA\Post(summary: 'Добавить картинку в галерею')]
    #[ImageUploadBody]
    #[OA\Response(response: 201, description: 'Картинка добавлена в конец галереи', content: new OA\JsonContent(ref: new Model(type: AdminProjectImageView::class)))]
    #[ProblemResponse(422, 'Нет файла, это не картинка или файл больше 10 МБ')]
    public function addImage(
        Project $project,
        #[MapUploadedFile(new Assert\Image(maxSize: '10M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']))]
        ?UploadedFile $file,
    ): JsonResponse {
        $path = $this->storage->store(self::requireFile($file), self::DIRECTORY);
        try {
            $image = $project->addImage($path);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $this->storage->delete([$path]);

            throw $exception;
        }

        return $this->json(AdminProjectImageView::fromEntity($image, $this->uploads), Response::HTTP_CREATED);
    }

    #[Route('/images/{imageId}', name: 'image_delete', requirements: ['imageId' => Requirement::DIGITS], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Удалить картинку из галереи')]
    #[OA\Response(response: 204, description: 'Картинка удалена')]
    public function deleteImage(Project $project, #[MapEntity(id: 'imageId')] ProjectImage $image): Response
    {
        if ($image->getProject() !== $project) {
            throw $this->createNotFoundException('The image does not belong to this project.');
        }

        $files = [$image->getPath(), ...array_values($image->getThumbnails())];
        $project->removeImage($image);
        $this->entityManager->flush();
        $this->storage->delete($files);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/cover', name: 'cover_set', methods: ['POST'])]
    #[OA\Post(summary: 'Загрузить или заменить обложку')]
    #[ImageUploadBody]
    #[OA\Response(response: 200, description: 'Проект с новой обложкой', content: new OA\JsonContent(ref: new Model(type: AdminProjectView::class)))]
    #[ProblemResponse(422, 'Нет файла, это не картинка или файл больше 10 МБ')]
    public function setCover(
        Project $project,
        #[MapUploadedFile(new Assert\Image(maxSize: '10M', mimeTypes: ['image/jpeg', 'image/png', 'image/webp']))]
        ?UploadedFile $file,
    ): JsonResponse {
        $previous = $project->getCoverImage();
        $path = $this->storage->store(self::requireFile($file), self::DIRECTORY);
        try {
            $project->setCoverImage($path);
            $this->entityManager->flush();
        } catch (Throwable $exception) {
            $this->storage->delete([$path]);

            throw $exception;
        }

        if (null !== $previous) {
            $this->storage->delete([$previous]);
        }

        return $this->json(AdminProjectView::fromEntity($project, $this->uploads));
    }

    #[Route('/cover', name: 'cover_delete', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Убрать обложку')]
    #[OA\Response(response: 204, description: 'Обложка удалена')]
    public function deleteCover(Project $project): Response
    {
        $previous = $project->getCoverImage();
        if (null !== $previous) {
            $project->setCoverImage(null);
            $this->entityManager->flush();
            $this->storage->delete([$previous]);
        }

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    // Nullable argument + explicit check: a missing file then gets a violation on "file" instead of a bare 422
    private static function requireFile(?UploadedFile $file): UploadedFile
    {
        return $file ?? throw InvalidRequest::at('file', 'Attach an image in the "file" form field.');
    }
}
