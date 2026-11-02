<?php

declare(strict_types=1);

// Exécution : php tests/form_submission_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\Controller\AbstractController;
use Mithridatem\Routing\Http\RequestInterface;

final class FormSubmissionControllerProbe extends AbstractController
{
    public function submitted(RequestInterface $request): bool
    {
        return $this->isFormSubmitted($request);
    }
}

function fakeRequest(string $method): RequestInterface
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

$controller = new FormSubmissionControllerProbe();

if ($controller->submitted(fakeRequest('GET'))) {
    throw new RuntimeException('GET ne doit pas être considéré comme soumis');
}

if (!$controller->submitted(fakeRequest('POST'))) {
    throw new RuntimeException('POST sans champ submit doit être considéré comme soumis');
}

if (!$controller->submitted(fakeRequest('post'))) {
    throw new RuntimeException('Méthode post en minuscules doit être acceptée');
}

if ($controller->submitted(fakeRequest('PUT'))) {
    throw new RuntimeException('PUT ne doit pas être considéré comme un POST');
}

echo "isFormSubmitted : OK\n";
