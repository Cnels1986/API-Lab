<?php
namespace nelson\api;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
require './vendor/autoload.php';
class App
{
  private $app;
  public function __construct($db) {

    $config['db']['host']   = 'localhost';
    $config['db']['user']   = 'root';
    $config['db']['pass']   = 'root';
    $config['db']['dbname'] = 'apidb';

    $app = new \Slim\App(['settings' => $config]);

    $container = $app->getContainer();
    $container['db'] = $db;

    $container['logger'] = function($c) {
      $logger = new \Monolog\Logger('my_logger');
      $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
      $logger->pushHandler($file_handler);
      return $logger;
    };


    // gets all games from the database
    $app->get('/games', function (Request $request, Response $response) {
      $this->logger->addInfo("GET /games");
      //query to select all the rows within the games table
      $game = $this->db->query('SELECT * from games')->fetchAll();
      $jsonResponse = $response->withJson($game);
      return $jsonResponse;
    });


    /*
    Function will find a game from the table based on the id given

    curl http://192.168.33.10/api/games/1
    */
    $app->get('/games/{id}', function (Request $request, Response $response, array $args) {
      // gets id from the parameters to use for the query
      $id = $args['id'];
      $this->logger->addInfo("GET /games/".$id);
      // query
      $game = $this->db->query('SELECT * from games where id='.$id)->fetch();

      if($game){
        $response =  $response->withJson($game);
      } else {
        $errorData = array('status' => 404, 'message' => 'not found');
        $response = $response->withJson($errorData, 404);
      }
      return $response;
    });


    /*
    Function will delete a game from the table from the id given

    curl -X DELETE  http://192.168.33.10/api/games/2
    */
    $app->delete('/games/{id}', function (Request $request, Response $response, array $args) {
      $id = $args['id'];
      $this->logger->addInfo("DELETE /games/".$id);
      // delete query
      $game = $this->db->exec('DELETE FROM games where id='.$id);
      if($game){
        $response = $response->withStatus(200);
      } else {
        $errorData = array('status' => 404, 'message' => 'not found');
        $response = $response->withJson($errorData, 404);
      }
      return $response;
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

      // checks if game exists
      $game = $this->db->query('SELECT * from games where id='.$id)->fetch();
      if(!$game){
        $errorData = array('status' => 404, 'message' => 'not found');
        $response = $response->withJson($errorData, 404);
        return $response;
      }

      // game exists, time to build query string
      $updateString = "UPDATE games SET ";
      $fields = $request->getParsedBody();
      $keysArray = array_keys($fields);
      $last_key = end($keysArray);
      foreach($fields as $field => $value) {
        $updateString = $updateString . "$field = '$value'";
        if ($field != $last_key) {
          // add comma
          $updateString = $updateString . ", ";
        }
      }
      $updateString = $updateString . " WHERE id = $id;";

      // execute query
      try {
        $this->db->exec($updateString);
      } catch (\PDOException $e) {
        $errorData = array('status' => 400, 'message' => 'Invalid data provided to update');
        return $response->withJson($errorData, 400);
      }

      // return updated record
      $game = $this->db->query('SELECT * from games ORDER BY id desc LIMIT 1')->fetch();
      $jsonResponse = $response->withJson($game);
      return $jsonResponse;
    });


    /*
    Function will add a row to the games table with the information provided.
    Similar to the update function, it constructs an INSERT query based on the data
    sent to it

    Test curl for adding a new game

    curl -X POST \
    http://192.168.33.10/api/games \
    -H 'Cache-Control: no-cache' \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    -H 'Postman-Token: a23837f2-2b01-4776-89a8-8b528bd94aec' \
    -d 'name=Doom&year=2016&console=PS4'
    */
    $app->post('/games', function (Request $request, Response $response) {
        $this->logger->addInfo("POST /games/");

        // creates a string to use that inserts that provided data into the table
        $addString = "INSERT INTO games ";
        $fields = $request->getParsedBody();
        $keysArray = array_keys($fields);
        $last_key = end($keysArray);
        $values = '(';
        $fieldNames = '(';
        // loops through and gets the keys and their values
        foreach($fields as $field => $value) {
          $values = $values . "'"."$value"."'";
          $fieldNames = $fieldNames . "$field";
          if ($field != $last_key) {
            // conditionally add a comma to avoid sql syntax problems
            $values = $values . ", ";
            $fieldNames = $fieldNames . ", ";
          }
        }
        $values = $values . ')';
        $fieldNames = $fieldNames . ') VALUES ';
        // combines everything together to make the insert query
        $addString = $addString . $fieldNames . $values . ";";


        try {
          $this->db->exec($addString);
        } catch (\PDOException $e) {
          var_dump($e);
          $errorData = array('status' => 400, 'message' => 'Invalid data provided to add game');
          return $response->withJson($errorData, 400);
        }

        $game = $this->db->query('SELECT * from games ORDER BY id desc LIMIT 1')->fetch();
        $jsonResponse = $response->withJson($game);

        return $jsonResponse;
    });

    $this->app = $app;
  }
  public function get()
  {
    return $this->app;
  }
}
