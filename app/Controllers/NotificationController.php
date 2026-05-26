<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\Flash;
use App\Services\NotificationService;

final class NotificationController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): void
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $type = isset($_GET['type']) ? (string) $_GET['type'] : null;
        $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === '1';

        $service = new NotificationService();
        $result = $service->search($page, self::PER_PAGE, $type, $unreadOnly);

        $filters = [
            'type' => $type ?? '',
            'unread' => $unreadOnly ? '1' : '',
        ];

        $this->view('notifications/index', [
            'notifications' => $result['items'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => self::PER_PAGE,
            'filters' => $filters,
            'paginationQuery' => NotificationService::filterQueryParams($filters),
            'typeLabels' => NotificationService::TYPE_LABELS,
            'types' => NotificationService::TYPES,
            'flash' => Flash::pull(),
        ]);
    }

    public function markRead(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
        {
            throw new ValidationException(['id' => 'Notificação inválida.']);
        }

        $service = new NotificationService();
        if (!$service->markAsRead($id))
        {
            throw new ValidationException(['id' => 'Notificação não encontrada ou já lida.']);
        }

        Flash::success('Notificação marcada como lida.');
        $this->redirectToNotificationsList();
    }

    public function markAllRead(): void
    {
        $service = new NotificationService();
        $count = $service->markAllAsRead();

        Flash::success(
            $count > 0
                ? sprintf('%d notificação(ões) marcada(s) como lida(s).', $count)
                : 'Nenhuma notificação pendente.'
        );

        $this->redirect('/notifications');
    }

    public function open(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0)
        {
            throw new ValidationException(['id' => 'Notificação inválida.']);
        }

        $service = new NotificationService();
        $this->redirect($service->markAsReadAndGetRedirectPath($id));
    }

    private function redirectToNotificationsList(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $query = '';
        if (is_string($referer) && $referer !== '')
        {
            $parsedQuery = parse_url($referer, PHP_URL_QUERY);
            if (is_string($parsedQuery) && $parsedQuery !== '')
            {
                $allowed = NotificationService::filterQueryParams(
                    $this->parseNotificationListQuery($parsedQuery)
                );
                if ($allowed !== [])
                {
                    $query = '?' . http_build_query($allowed);
                }
            }
        }

        $this->redirect('/notifications' . $query);
    }

    /**
     * @return array{type: string, unread: string}
     */
    private function parseNotificationListQuery(string $queryString): array
    {
        parse_str($queryString, $params);

        return [
            'type' => isset($params['type']) ? (string) $params['type'] : '',
            'unread' => isset($params['unread']) && (string) $params['unread'] === '1' ? '1' : '',
        ];
    }
}
