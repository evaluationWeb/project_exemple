<?php

declare(strict_types=1);

// Lancer : php tests/form_submission_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\AbstractController;
use Mithridatem\Routing\Http\RequestInterface;

final class SubmissionProbe extends AbstractController
{
    public function isSubmitted(RequestInterface $request): bool
    {
        return $this->isFormSubmitted($request);
    }
}

function requestWithMethod(string $method): RequestInterface
{
    return new class($method) implements RequestInterface {
        public function __construct(private string $method) {}

        public function getMethod(): string { return $this->method; }
        public function getPath(): string { return '/register'; }
        public function getBearerToken(): ?string { return null; }
        public function query(string $name, mixed $default = null): mixed { return $default; }
        public function post(string $name, mixed $default = null): mixed { return $default; }
        public function header(string $name, ?string $default = null): ?string { return $default; }
        public function getContent(): string { return ''; }
    };
}

$controller = new SubmissionProbe();

foreach (['GET' => false, 'POST' => true, 'post' => true, 'PUT' => false, 'DELETE' => false] as $method => $expected) {
    if ($controller->isSubmitted(requestWithMethod($method)) !== $expected) {
        throw new RuntimeException("Résultat inattendu pour $method");
    }
}

echo "isFormSubmitted : OK\n";
