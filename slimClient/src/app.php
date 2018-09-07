<?php
namespace nelson\apiClient;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\PhpRenderer;

require './vendor/autoload.php';

class App
{
   private $app;

   public function __construct() {

     $app = new \Slim\App(['settings' => $config]);

     $container = $app->getContainer();

     $container['logger'] = function($c) {
         $logger = new \Monolog\Logger('my_logger');
         $file_handler = new \Monolog\Handler\StreamHandler('./logs/app.log');
         $logger->pushHandler($file_handler);
         return $logger;
     };
     $container['renderer'] = new PhpRenderer("./templates");

     function makeApiRequest($path) {
       $ch = curl_init();

       //Set the URL that you want to GET by using the CURLOPT_URL option.
       curl_setopt($ch, CURLOPT_URL, "http://localhost/api/$path");
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

       $response = curl_exec($ch);
       return json_decode($response, true);
     }

     

     $app->get('/', function (Request $request, Response $response, array $args) {
       $responseRecords = makeApiRequest('games');
       $tableRows = "";
       foreach($responseRecords as $game) {
         $tableRows = $tableRows . "<tr>";
         $tableRows = $tableRows . "<td>".$game["name"]."</td><td>".$game["console"]."</td><td>".$game["year"]."</td>";
         $tableRows = $tableRows . "<td>
         <a href='http://localhost:8080/slimClient/games/".$game["id"]."' class='btn btn-primary'>View Details</a>
         <a href='http://localhost:8080/slimClient/games/".$game["id"]."/edit' class='btn btn-secondary'>Edit</a>
         <a data-id='".$game["id"]."' class='btn btn-danger deletebtn'>Delete</a>

         </td>";
         $tableRows = $tableRows . "</tr>";
       }

       $templateVariables = [
           "title" => "Games",
           "tableRows" => $tableRows
       ];
       return $this->renderer->render($response, "/games.html", $templateVariables);
     });



     $app->get('/games/add', function(Request $request, Response $response) {
       $templateVariables = [
         "type" => "new",
         "title" => "Add new game"
       ];
       return $this->renderer->render($response, "/gamesForm.html", $templateVariables);
     });

     $this->app = $app;
   }
   /**
    * Get an instance of the application.
    *
    * @return \Slim\App
    */
   public function get()
   {
       return $this->app;
   }
 }
