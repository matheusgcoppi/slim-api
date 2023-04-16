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

$app->post('/customer', function(Request $request, Response $response) {
    $data = $request->getParsedBody();
    $name = $data['name'];
    $email = $data['email'];
    $phone = $data['phone'];

    try {
        $query = "INSERT INTO customers (name, email, phone) VALUES ('$name', '$email', '$phone')";
        $db = new Database();
        $connection = $db->connect();
        $stmt = $connection->query($query);

        if($stmt) {
            $query2 = "SELECT * FROM customers WHERE name = '$name' AND email = '$email' AND phone = '$phone' ORDER BY id DESC LIMIT 1";
            $db = new Database();
            $connection = $db->connect();
            $stmt = $connection->query($query2);
            $customer = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode($customer));
            return $response
                    ->withHeader('Content-Type', 'text/plain')
                    ->withStatus(201);

        } else {
            throw new Exception('Create customer failed');
        }
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


$app->get('/customer/all', function (Request $request, Response $response) {
    
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

$app->get('/customer/{id}', function (Request $request, Response $response, array $args ) {
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

$app->put('/customer/update/{id}', function(Request $request, Response $response, array $args) {
    try {
        $data = $request->getParsedBody();
        $id = $args['id'];
        $name = $data['name'];
        $email = $data['email'];
        $phone = $data['phone'];

        if(empty($id)) {
            throw new Exception('ID is empty');
        }

        $query = "UPDATE customers SET email='$email', name= '$name', phone='$phone' WHERE id=$id";
        $db = new Database();
        $connection = $db->connect();
        $stmt = $connection->query($query);
       
        if($stmt) {

            $query2 = "SELECT * FROM customers WHERE id=$id";
            $stmt = $connection->query($query2);
            $customerUpdated = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            $response->getBody()->write(json_encode($customerUpdated));
            return $response
                    ->withHeader('Content-Type', 'applications/json')
                    ->withStatus(201);

        } else {
            throw new Exception('Update failed');
        }

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

$app->delete('/customer/delete/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    try {
        $query = "DELETE FROM customers WHERE id = :id AND NOT EXISTS(SELECT 1 FROM customers WHERE id = :id)";
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $result = $stmt->execute();

        if($stmt->rowCount() < 0 ) {
            $responseData = array(
                "message"=> "The user by id " .$id . " was deleted",
                "result" => $result
            );
           
            $response->getBody()->write(json_encode($responseData));
            return $response
                    ->withHeader('Content-Type', 'applications/json')
                    ->withStatus(200);
        }
        else {
            $responseData = array(
                "message"=> "The user by id " .$id . " was not found",
                "result" => $result
            );
           
            $response->getBody()->write(json_encode($responseData));
            return $response
                    ->withHeader('Content-Type', 'applications/json')
                    ->withStatus(404);  
        }

    } catch (PDOException $error) {
        $error = array(
            "message" => $error->getMessage()
        );

        $response->getBody()->write(json_encode($error));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});




// Run app
$app->run();