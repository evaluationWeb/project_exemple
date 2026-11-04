<?php

declare(strict_types=1);

namespace App\Middleware;

/** Raised when a submitted form token is missing or invalid. */
final class InvalidCsrfTokenException extends \RuntimeException {}
