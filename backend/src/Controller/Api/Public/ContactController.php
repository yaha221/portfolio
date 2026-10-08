<?php

declare(strict_types=1);

namespace App\Controller\Api\Public;

use App\Dto\Request\ContactRequest;
use App\Entity\ContactMessage;
use App\Message\ContactMessageReceived;
use App\Messenger\BestEffortDispatcher;
use App\OpenApi\ProblemResponse;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

#[OA\Tag(name: 'Contact')]
final class ContactController extends AbstractController
{
    #[Route('/api/contact', name: 'api_contact_send', methods: ['POST'], format: 'json')]
    #[OA\Post(
        summary: 'Отправить сообщение с формы контакта',
        description: 'Сообщение сохраняется сразу, письмо и уведомление в Telegram отправляет воркер. Не больше 3 сообщений за 10 минут с одного IP.',
    )]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: ContactRequest::class)))]
    #[OA\Response(response: 202, description: 'Сообщение принято')]
    #[ProblemResponse(422, 'Ошибки валидации')]
    #[ProblemResponse(429, 'Слишком много сообщений, есть заголовок Retry-After')]
    public function __invoke(
        #[MapRequestPayload]
        ContactRequest $request,
        Request $httpRequest,
        RateLimiterFactoryInterface $contactFormLimiter,
        EntityManagerInterface $entityManager,
        BestEffortDispatcher $dispatcher,
    ): Response {
        $limit = $contactFormLimiter->create($httpRequest->getClientIp() ?? 'unknown')->consume();
        if (!$limit->isAccepted()) {
            throw new TooManyRequestsHttpException(max(1, $limit->getRetryAfter()->getTimestamp() - time()), 'Too many messages, please try again later.');
        }

        // Bots fill every field; people never see the hidden "website" field. Pretend success.
        if ('' !== $request->website) {
            return new Response(null, Response::HTTP_ACCEPTED);
        }

        $message = new ContactMessage(trim($request->name), $request->email, trim($request->message));
        $entityManager->persist($message);
        $entityManager->flush();

        $dispatcher->dispatch(new ContactMessageReceived((int) $message->getId()));

        return new Response(null, Response::HTTP_ACCEPTED);
    }
}
