<?php

namespace App\Controller;

use Mithridatem\Routing\Http\RequestInterface;

abstract class AbstractController
{
    /**
     * Méthode pour rendre une vue avec un template
     * @param string $template Le nom du template à inclure
     * @param string|null $title Le titre de la page
     * @param array $data Les données à passer au template
     * @return mixed
     */
    protected function render(string $template, ?string $title, array $data = []): mixed
    {
        return include __DIR__ . "/../../template/template_" . $template . ".php";
    }

    /**
     * Méthode pour générer un composant
     * @param string $component Le nom du template à inclure
     * @param array $data Les données à passer au composant
     * @return void
     */
    protected function renderComponent(string $component, array $data = []): void
    {
        include __DIR__ . "/../../template/component/component_" . $component . ".php";
    }

    /**
     * Méthode pour envoyer une réponse JSON
     * @param array $data Les données à encoder en JSON
     * @param int $statusCode Le code de statut HTTP
     * @return void
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Indique si la requête correspond à une soumission HTTP POST.
     * Ne dépend pas de la présence d'un bouton submit nommé dans le formulaire.
     */
    protected function isFormSubmitted(RequestInterface $request): bool
    {
        return strtoupper($request->getMethod()) === 'POST';
    }

    /**
     * Méthode pour rediriger vers une route
     * @param string $route route de redirection
     * @return void
     */
    protected function redirect(string $route): void
    {
        header("Location: " . $route, true, 303);
        exit;
    }
}
