<?php

declare(strict_types=1);

// Lancer : php tests/request_dto_mapping_test.php
require dirname(__DIR__) . '/vendor/autoload.php';

use App\DTO\LoginInput;
use App\DTO\RegisterUserInput;
use Mithridatem\Routing\Http\ParameterCollectionRequestInterface;
use Mithridatem\Routing\Http\Request;
use Mithridatem\Routing\Route;
use Mithridatem\Routing\Router;

function checkMapping(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$router = new Router();
$router->map(Route::post('/register', static fn (ParameterCollectionRequestInterface $request)
    => RegisterUserInput::fromArray($request->allPost())));
$router->map(Route::post('/login', static fn (ParameterCollectionRequestInterface $request)
    => LoginInput::fromArray($request->allPost())));

$registration = $router->dispatch(new Request('/register', 'POST', post: [
    'firstname' => ' Ada ',
    'lastname' => ' Lovelace ',
    'email' => ' ada@example.test ',
    'password' => ' secret ',
    'confirm_password' => ' secret ',
    'csrf_token' => 'csrf-value',
    'unused' => 'ignored',
]));

checkMapping($registration instanceof RegisterUserInput, 'Register : DTO incorrect');
checkMapping($registration->firstname === 'Ada', 'Register : firstname');
checkMapping($registration->lastname === 'Lovelace', 'Register : lastname');
checkMapping($registration->email === 'ada@example.test', 'Register : email');
checkMapping($registration->password === ' secret ', 'Register : password modifié');
checkMapping($registration->confirmPassword === ' secret ', 'Register : confirmation modifiée');

$login = $router->dispatch(new Request('/login', 'POST', post: [
    'email' => ' ada@example.test ',
    'password' => ' secret ',
    'csrf_token' => 'csrf-value',
]));
checkMapping($login instanceof LoginInput, 'Login : DTO incorrect');
checkMapping($login->email === 'ada@example.test', 'Login : email');
checkMapping($login->password === ' secret ', 'Login : password modifié');

$invalid = $router->dispatch(new Request('/login', 'POST', post: [
    'email' => ['invalid'],
    'password' => ['invalid'],
]));
checkMapping($invalid->email === null, 'Login : tableau email non rejeté');
checkMapping($invalid->password === null, 'Login : tableau password non rejeté');

echo "Request DTO mapping : OK\n";
