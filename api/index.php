<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require './vendor/autoload.php';

$config['db']['host']   = 'localhost';
$config['db']['user']   = 'root';
$config['db']['pass']   = 'root';
$config['db']['dbname'] = 'apidb';

$app = new \Slim\App(['settings' => $config]);

$container = $app->getContainer();

$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

// test function i use to make sure app is working, returns Hello, ______
$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];

    $test1 = "test";
    $test2 = "This is a test (" . $test1 . ")";
    $response->getBody()->write($test2);
    // $response->getBody()->write("Hello, $name");
    return $response;
});

// gets all games from the database
$app->get('/games', function (Request $request, Response $response) {
    $this->logger->addInfo("GET /games");
    //query to select all the rows within the games table
    $game = $this->db->query('SELECT * from games')->fetchAll();
    $jsonResponse = $response->withJson($game);
    return $jsonResponse;
});

// finds a game within the table by its id
$app->get('/games/{id}', function (Request $request, Response $response, array $args) {
    // gets id from the parameters to use for the query
    $id = $args['id'];
    $this->logger->addInfo("GET /games/".$id);
    // query
    $game = $this->db->query('SELECT * from games where id='.$id)->fetch();
    $jsonResponse = $response->withJson($game);
    return $jsonResponse;
});

// deletes a game from the table based on the id passed in
$app->delete('/games/{id}', function (Request $request, Response $response, array $args) {
  $id = $args['id'];
  $this->logger->addInfo("DELETE /games/".$id);
  $game = $this->db->exec('DELETE FROM games where id='.$id);
  $jsonResponse = $response->withJson($game);
  return;
});

/*
Function will find the game based on the given id then update the different fields with the provided information

Test curl for the update function:

curl -X PUT \
 http://192.168.33.10/api/games/1 \
 -H 'Cache-Control: no-cache' \
 -H 'Content-Type: application/x-www-form-urlencoded' \
 -H 'Postman-Token: a23837f2-2b01-4776-89a8-8b528bd94aec' \
 -d 'name=StarCraft&year=1998&console=PC'
*/
$app->put('/games/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $this->logger->addInfo("PUT /games/".$id);
    // build query string
    $updateString = "UPDATE games SET ";
    $fields = $request->getParsedBody();
    $keysArray = array_keys($fields);
    $last_key = end($keysArray);
    foreach($fields as $field => $value) {
      $updateString = $updateString . "$field = '$value'";
      if ($field != $last_key) {
        // conditionally add a comma to avoid sql syntax problems
        $updateString = $updateString . ", ";
      }
    }
    $updateString = $updateString . " WHERE id = $id;";
    // execute query
    $this->db->exec($updateString);
    // return updated record
    $game = $this->db->query('SELECT * from games where id='.$id)->fetch();
    $jsonResponse = $response->withJson($game);
    return $jsonResponse;
});

// INSERT INTO games (id, name, year, console) VALUES (5, Super Mario Bros, 1985, NES)
// curl -X POST \
//  http://192.168.33.10/api/games \
//  -H 'Cache-Control: no-cache' \
//  -H 'Content-Type: application/x-www-form-urlencoded' \
//  -H 'Postman-Token: a23837f2-2b01-4776-89a8-8b528bd94aec' \
//  -d 'id=5&name=Doom&year=2016&console=PS4'

$app->post('/games', function (Request $request, Response $response, array $args) {
    $this->logger->addInfo("POST /games");

    $id = $args['id'];
    $addString = "INSERT INTO games ";
    $addString = $addString . "(";

    $fields = $request->getParsedBody();
    $keysArray = array_keys($fields);
    $last_key = end($keysArray);

    // first loop adds the different column names
    foreach($fields as $field => $value) {
      $addString = $addString . "$field";
      if( $field != $last_key) {
        // adds the comma between values
        $addString = $addString . ", ";
      }
    }

    $addString = $addString . ") VALUES (";
    // second loop adds the values for each of the columns
    foreach($fields as $field => $value) {
      $addString = $addString . "$value";
      if($field != $last_key) {
        $addString = $addString . ", ";
      }
    }
    // closes the create query from the information sent
    $addString = $addString . ");";

    $this->db->exec($updateString);
    // return updated record
    // $game = $this->db->query('SELECT * from games where id='.$id)->fetch();
    // $jsonResponse = $response->withJson($game);
    // return $jsonResponse;

});

$app->run();
