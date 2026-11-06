<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Middleware\InvalidCsrfTokenException;
use Mithridatem\Routing\Exception\MethodNotAllowedException;
use Mithridatem\Routing\Exception\RouteNotFoundException;
use Mithridatem\Routing\Exception\RouterException;
use Mithridatem\Routing\Exception\UnauthorizedException;
use Throwable;

/** Converts errors into minimal, safely escaped HTML responses. */
final class WebErrorHandler
{
    public function handle(Throwable $error): void
    {
        $status = match (true) {
            $error instanceof InvalidCsrfTokenException,
            $error instanceof UnauthorizedException => 403,
            $error instanceof RouteNotFoundException => 404,
            $error instanceof MethodNotAllowedException => 405,
            $error instanceof RouterException =>
                ($error->getCode() >= 400 && $error->getCode() <= 599)
                    ? $error->getCode()
                    : 500,
            default => 500,
        };

        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        if ($error instanceof MethodNotAllowedException) {
            header('Allow: ' . implode(', ', $error->getAllowedMethods()));
        }

        // Do not reveal internals (database errors, paths, stack traces) to clients.
        $message = $status === 500 ? 'Erreur interne du serveur' : $error->getMessage();
        echo '<h1>' . $status . '</h1><p>' .
            htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
            '</p>';
    }
}
