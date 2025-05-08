<?php
namespace Core\Api;

/**
 * Gère les réponses API standardisées
 */
class ApiResponse
{
    /**
     * Envoie une réponse JSON
     * 
     * @param mixed $data Les données à retourner
     * @param int $statusCode Code HTTP à retourner
     * @param array $headers En-têtes HTTP additionnels
     * @return void
     */
    public function json($data, int $statusCode = 200, array $headers = [])
    {
        http_response_code($statusCode);
        
        // Définir les en-têtes par défaut pour l'API
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Ajouter les en-têtes additionnels
        foreach ($headers as $name => $value) {
            header("{$name}: {$value}");
        }
        
        // Encoder en JSON et renvoyer la réponse
        echo json_encode($data);
        exit;
    }
    
    /**
     * Envoie une réponse de succès standardisée
     * 
     * @param mixed $data Les données à retourner
     * @param string $message Message de succès
     * @param int $statusCode Code HTTP à retourner
     * @return void
     */
    public function success($data = null, string $message = 'Success', int $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->json($response, $statusCode);
    }
    
    /**
     * Envoie une réponse d'erreur standardisée
     * 
     * @param string $message Message d'erreur
     * @param int $statusCode Code HTTP à retourner
     * @param array $errors Détails des erreurs
     * @return void
     */
    public function error(string $message = 'Error', int $statusCode = 400, array $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        $this->json($response, $statusCode);
    }
    
    /**
     * Envoie une réponse 404 Not Found
     * 
     * @param string $message Message d'erreur
     * @return void
     */
    public function notFound(string $message = 'Resource not found')
    {
        $this->error($message, 404);
    }
    
    /**
     * Envoie une réponse 401 Unauthorized
     * 
     * @param string $message Message d'erreur
     * @return void
     */
    public function unauthorized(string $message = 'Unauthorized')
    {
        $this->error($message, 401);
    }
    
    /**
     * Envoie une réponse 403 Forbidden
     * 
     * @param string $message Message d'erreur
     * @return void
     */
    public function forbidden(string $message = 'Forbidden')
    {
        $this->error($message, 403);
    }
    
    /**
     * Envoie une réponse 201 Created
     * 
     * @param mixed $data Les données de la ressource créée
     * @param string $message Message de succès
     * @return void
     */
    public function created($data = null, string $message = 'Resource created')
    {
        $this->success($data, $message, 201);
    }
    
    /**
     * Envoie une réponse 204 No Content
     * 
     * @return void
     */
    public function noContent()
    {
        http_response_code(204);
        exit;
    }
    
    /**
     * Envoie une réponse de pagination standardisée
     * 
     * @param array $items Les éléments de la page courante
     * @param int $total Nombre total d'éléments
     * @param int $page Page courante
     * @param int $perPage Nombre d'éléments par page
     * @return void
     */
    public function paginate(array $items, int $total, int $page, int $perPage)
    {
        $lastPage = ceil($total / $perPage);
        
        $response = [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'total' => $total
            ],
            'links' => [
                'first' => '?page=1',
                'last' => "?page={$lastPage}",
                'prev' => $page > 1 ? "?page=" . ($page - 1) : null,
                'next' => $page < $lastPage ? "?page=" . ($page + 1) : null
            ]
        ];
        
        $this->json($response);
    }
}