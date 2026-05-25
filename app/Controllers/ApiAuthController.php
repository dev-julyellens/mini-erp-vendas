<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ValidationException;
use App\Helpers\ApiResponse;
use App\Middleware\ApiMiddleware;
use App\Services\ApiAuthService;
use App\Services\ApiPayloadService;

final class ApiAuthController extends Controller
{
    public function login(): void
    {
        $payloadService = new ApiPayloadService();

        try
        {
            $payload = $payloadService->requireJsonObject();
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }

        $errors = $payloadService->validateRequired($payload, ['email', 'password']);
        if ($errors !== [])
        {
            $this->json(['success' => false, 'errors' => $errors], 422);
        }

        $service = new ApiAuthService();
        try
        {
            $result = $service->login(
                (string) $payload['email'],
                (string) $payload['password']
            );
            ApiMiddleware::attachUserToLog($result['user']['id']);
            ApiResponse::send([
                'success' => true,
                'data' => $result,
            ], 200);
        }
        catch (ValidationException $e)
        {
            $this->json(['success' => false, 'errors' => $e->getErrors()], 422);
        }
        catch (\Throwable $e)
        {
            ApiResponse::error('Erro interno do servidor.', 500);
        }
    }
}
