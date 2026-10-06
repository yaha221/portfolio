<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Dto\Request\PageQuery;
use App\Dto\Response\Admin\ContactMessagePage;
use App\Dto\Response\Admin\ContactMessageView;
use App\Dto\Response\PaginationMeta;
use App\OpenApi\ProblemResponse;
use App\Repository\ContactMessageRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Admin: messages')]
#[Security(name: 'Bearer')]
final class ContactMessageAdminController extends AbstractController
{
    #[Route('/api/admin/messages', name: 'api_admin_messages_list', methods: ['GET'], format: 'json')]
    #[OA\Get(summary: 'Сообщения с формы контакта', description: 'Сначала новые.')]
    #[OA\Response(response: 200, description: 'Страница сообщений', content: new OA\JsonContent(ref: new Model(type: ContactMessagePage::class)))]
    #[ProblemResponse(400, 'Неверные параметры запроса')]
    #[ProblemResponse(401, 'Нет access-токена или он истёк')]
    #[ProblemResponse(403, 'Нет роли ROLE_ADMIN')]
    public function list(
        ContactMessageRepository $messages,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)]
        PageQuery $query = new PageQuery(),
    ): JsonResponse {
        $paginator = $messages->findPage($query->page, $query->perPage);

        $items = [];
        foreach ($paginator as $message) {
            $items[] = ContactMessageView::fromEntity($message);
        }

        return $this->json(new ContactMessagePage($items, PaginationMeta::of($query->page, $query->perPage, \count($paginator))));
    }
}
