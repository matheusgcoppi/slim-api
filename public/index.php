<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;
use Config\Database; 

require_once __DIR__ . '/../vendor/autoload.php';


$app = AppFactory::create();

// Add Slim routing middleware
$app->addRoutingMiddleware();

// Set the base path to run the app in a subdirectory.
// This path is used in urlFor().
$app->add(new BasePathMiddleware($app));

$app->addErrorMiddleware(true, true, true);

// Define app routes
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('Hello, World!!');
    return $response;
})->setName('root');


$app->get('/customers/all', function (Request $request, Response $response) {
    
    try {
        $query = "SELECT * FROM customers";
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->query($query);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
        $response->getBody()->write(json_encode($customers));
        return $response
            ->withHeader('Content-Type', 'text/plain')
            ->withStatus(200);

    } catch (PDOException $error) {
        $error = array(
            "message" => $error->getMessage()
        );

        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'applications/json')
            ->withStatus(500);
    }
});

$app->get('/customers/{id}', function (Request $request, Response $response, array $args ) {
    try {
        $id = $args['id'];
        $query = "SELECT * FROM customers WHERE id = $id";
        $db = new Database();
        $connection = $db->connect();
        $stmt = $connection->query($query);

        $customer = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if(empty($customer)) {
            $error = array(
                "message" => "Error Customer Not Found"
            );
    
            $response->getBody()->write(json_encode($error));
            return $response
            ->withHeader('Content-Type', 'applications/json')
            ->withStatus(404);
        }



        $response->getBody()->write(json_encode($customer));
        return $response 
               ->withHeader('Content-Type', 'applications/json')
               ->withStatus(200);
    } catch (PDOException $error) {
        $error = array(
            "message" => $error->getMessage()
        );

        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'applications/json')
            ->withStatus(500);
    }
});


// Run app
$app->run();