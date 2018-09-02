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

     // test function i use to make sure app is working, returns Hello, ______
     $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
         $name = $args['name'];
         $this->logger->addInfo('get request to /hello/'.$name);
         $response->getBody()->write("Hello, $name");
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
         $jsonResponse = $response->withJson($game);
         return $jsonResponse;
     });

     /*
     Function will delete a game from the table from the id given

     curl -X DELETE  http://192.168.33.10/api/games/2
     */
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

     /*
     Function will add a row to the games table with the information provided.
     Similar to the update function, it constructs an INSERT query based on the data
     sent to it

     Test curl for adding a new game, id may need to be changed

     curl -X POST \
      http://192.168.33.10/api/games \
      -H 'Cache-Control: no-cache' \
      -H 'Content-Type: application/x-www-form-urlencoded' \
      -H 'Postman-Token: a23837f2-2b01-4776-89a8-8b528bd94aec' \
      -d 'id=8&name=Doom&year=2016&console=PS4'
     */
     $app->post('/games', function (Request $request, Response $response, array $args) {
         $this->logger->addInfo("POST /games");

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
           //adds quotes for the 2 strings that could be used for the query
           if($field == "name" || $field == "console") {
             $addString = $addString . " '$value' ";
           }
           //numbers
           else {
             $addString = $addString . "$value";
           }
           if($field != $last_key) {
             $addString = $addString . ", ";
           }
         }
         // closes the create query from the information sent
         $addString = $addString . ");";
         // query is executed
         $this->db->exec($addString);
     });

     $this->app = $app;
   }
   public function get()
   {
       return $this->app;
   }
 }
