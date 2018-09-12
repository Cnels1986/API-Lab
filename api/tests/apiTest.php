<?php
use PHPUnit\Framework\TestCase;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Http\RequestBody;

require './vendor/autoload.php';

// empty class definitions for phpunit to mock.
class mockQuery {
  public function fetchAll(){}
  public function fetch(){}
};
class mockDb {
  public function query(){}
  public function exec(){}
};

class TodoTest extends TestCase
{
  protected $app;
  protected $db;

  public function setUp()
  {
    $this->db = $this->createMock('mockDb');
    $this->app = (new nelson\api\App($this->db))->get();
  }

  // test the GET games endpoint
  public function testGetGames() {
    // expected result string
    $resultString = '[{"id":"1","name":"Goldeneye 64","year":"1997","console":"Nintendo 64"},{"id":"2","name":"Final Fantasy X","year":"2001","console":"Playstation 2"}]';

    // mock the query class & fetchAll functions
    $query = $this->createMock('mockQuery');
    $query->method('fetchAll')->willReturn(json_decode($resultString, true));
    $this->db->method('query')->willReturn($query);

    // mock the request environment.  (part of slim)
    $env = Environment::mock([
      'REQUEST_METHOD' => 'GET',
      'REQUEST_URI'    => '/games',
    ]);
    $req = Request::createFromEnvironment($env);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame($resultString, (string)$response->getBody());
  }



  // Test to get a single game from the table
  public function testGetGame() {
    // test successful request
    $resultString = '{"id":"1","name":"Goldeneye 64","year":"1997","console":"Nintendo 64"}';

    $query = $this->createMock('mockQuery');
    $query->method('fetch')->willReturn(json_decode($resultString, true));
    $this->db->method('query')->willReturn($query);
    $env = Environment::mock([
      'REQUEST_METHOD' => 'GET',
      'REQUEST_URI'    => '/games/1',
    ]);
    $req = Request::createFromEnvironment($env);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame($resultString, (string)$response->getBody());
  }



  // This test will test whether or not we can update the information for a game within the table
  public function testUpdateGame() {
    // expected result string
    $resultString = '{"id":"1","name":"Chrono Cross","year":"1999","console":"Playstation 1"}';

    // mock the query class & fetchAll functions
    $query = $this->createMock('mockQuery');
    $query->method('fetch')->willReturn(json_decode($resultString, true));
    $this->db->method('query')->willReturn($query);
    $this->db->method('exec')->willReturn(true);

    // mock the request environment.  (part of slim)
    $env = Environment::mock([
      'REQUEST_METHOD' => 'PUT',
      'REQUEST_URI'    => '/games/1',
    ]);
    $req = Request::createFromEnvironment($env);
    $requestBody = ["name" => "Chrono Cross", "year" => "1999", "console" => "Playstation 1"];
    $req =  $req->withParsedBody($requestBody);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame($resultString, (string)$response->getBody());
  }



  // Function will test whether the delete function is working for the api
  public function testDeleteGame() {
    $query = $this->createMock('mockQuery');
    $this->db->method('exec')->willReturn(true);
    $env = Environment::mock([
      'REQUEST_METHOD' => 'DELETE',
      'REQUEST_URI'    => '/games/1',
    ]);
    $req = Request::createFromEnvironment($env);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame(200, $response->getStatusCode());
  }



  // fuction will deal with error handling when it comes to finding a game by its id
  public function testGetGameFailed() {
    $query = $this->createMock('mockQuery');
    $query->method('fetch')->willReturn(false);
    $this->db->method('query')->willReturn($query);
    $env = Environment::mock([
      'REQUEST_METHOD' => 'GET',
      'REQUEST_URI'    => '/games/1',
    ]);
    $req = Request::createFromEnvironment($env);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame($response->getStatusCode(), 404);
    $this->assertSame('{"status":404,"message":"not found"}', (string)$response->getBody());
  }



  // function deals with the error handling if the wrong data is sent when api is trying to update a game
  public function testUpdateGameFailed() {
    // expected result string
    $resultString = '{"id":"1","name":"Chrono Cross","year":"1999","console":"Playstation 1"}';

    // mock the query class & fetchAll functions
    $query = $this->createMock('mockQuery');
    $query->method('fetch')->willReturn(json_decode($resultString, true));
    $this->db->method('query')->willReturn($query);
    //mocks where the update failed!!!
    $this->db->method('exec')->will($this->throwException(new PDOException()));

    // mock the request environment.  (part of slim)
    $env = Environment::mock([
    'REQUEST_METHOD' => 'PUT',
    'REQUEST_URI'    => '/games/1',
    ]);
    $req = Request::createFromEnvironment($env);
    $requestBody = ["name" => "Chrono Cross", "year" => "1999", "console" => "Playstation 1"];
    $req =  $req->withParsedBody($requestBody);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame('{"status":400,"message":"Invalid data provided to update"}', (string)$response->getBody());
  }


  // Function will test that the API can handle a game not being found in the table when trying to update it
  public function testUpdateGameNotFound() {
    // expected result string
    $resultString = '{"id":"1","name":"Chrono Cross","year":"1999","console":"Playstation 1"}';

    // mock the query class & fetchAll functions
    $query = $this->createMock('mockQuery');
    $query->method('fetch')->willReturn(false);
    $this->db->method('query')->willReturn($query);
    $this->db->method('exec')->will($this->throwException(new PDOException()));

    // mock the request environment.  (part of slim)
    $env = Environment::mock([
    'REQUEST_METHOD' => 'PUT',
    'REQUEST_URI'    => '/games/1',
    ]);
    $req = Request::createFromEnvironment($env);
    $requestBody = ["name" => "Chrono Cross", "year" => "1999", "console" => "Playstation 1"];
    $req =  $req->withParsedBody($requestBody);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame(404, $response->getStatusCode());
    $this->assertSame('{"status":404,"message":"not found"}', (string)$response->getBody());
  }


  // Function will test a failure when the user trys to delete/remove a game from the table
  public function testDeleteGameFailed() {
    $query = $this->createMock('mockQuery');
    $this->db->method('exec')->willReturn(false);
    $env = Environment::mock([
    'REQUEST_METHOD' => 'DELETE',
    'REQUEST_URI'    => '/games/1',
    ]);
    $req = Request::createFromEnvironment($env);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    // assert expected status code and body
    $this->assertSame(404, $response->getStatusCode());
    $this->assertSame('{"status":404,"message":"not found"}', (string)$response->getBody());
  }


  // Function will test whether the create/post function is working in the API
  public function testCreateGame() {
    // test successful request
    $resultString = '{"id":"10","name":"Super Mario 64","year":"1997","console":"Nintendo 64"}';

    // mock the query class & fetchAll functions
    $query = $this->createMock('mockQuery');
    $query->method('fetch')->willReturn(json_decode($resultString, true));
    $this->db->method('query')->willReturn($query);
    $this->db->method('exec')->willReturn(true);

    $env = Environment::mock([
      'REQUEST_METHOD' => 'POST',
      'REQUEST_URI' => '/games',
    ]);

    $req = Request::createFromEnvironment($env);
    $requestBody = ["name" => "Super Mario 64", "year" => "1997", "console" => "Nintendo 64"];
    $req =  $req->withParsedBody($requestBody);
    $this->app->getContainer()['request'] = $req;

    $response = $this->app->run(true);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame($resultString, (string)$response->getBody());
  }


  // Function will test a failure when trying to create/add a new game to the table
  public function testCreateGameFailed() {
    // test successful request
    $resultString = '{"id":"10","name":"Super Mario 64","year":"1997","console":"Nintendo 64"}';

    // mock the query class & fetchAll functions
    $query = $this->createMock('mockQuery');
    $query->method('fetch')->willReturn(json_decode($resultString, true));
    $this->db->method('query')->willReturn($query);
    //mocks where the update failed!!!
    $this->db->method('exec')->will($this->throwException(new PDOException()));

    $env = Environment::mock([
      'REQUEST_METHOD' => 'POST',
      'REQUEST_URI'    => '/games',
    ]);
    $req = Request::createFromEnvironment($env);
    $requestBody = ["name" => "Super Mario 64", "year" => "1997", "console" => "Nintendo 64"];
    $req =  $req->withParsedBody($requestBody);
    $this->app->getContainer()['request'] = $req;

    // actually run the request through the app.
    $response = $this->app->run(true);
    $this->assertSame(400, $response->getStatusCode());
    // $this->assertSame('{"status":400,"message":"Invalid data provided to create entry"}', (string)$response->getBody());
  }
}
