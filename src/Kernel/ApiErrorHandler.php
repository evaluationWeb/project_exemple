<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Security\Jwt\InvalidJwtException;
use Mithridatem\Routing\Exception\MethodNotAllowedException;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Exception\RouterException;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Throwable;

/** Central API exception mapping: JSON responses, no internal error disclosure. */
final class ApiErrorHandler
{
    public function handle(Throwable $error): void
    {
        $status = match (true) {
            $error instanceof InvalidJwtException => 401,
            $error instanceof UnauthorizedException => 403,
            $error instanceof RouteNotFoundException => 404,
            $error instanceof MethodNotAllowedException => 405,
            default => 500,
        };

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if ($status === 401) {
            header('WWW-Authenticate: Bearer');
        }
        if ($error instanceof MethodNotAllowedException) {
            header('Allow: ' . implode(', ', $error->getAllowedMethods()));
        }

        $message = match ($status) {
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            default => 'Internal Server Error',
        };

        echo json_encode(['error' => $message], JSON_THROW_ON_ERROR);
    }
}
