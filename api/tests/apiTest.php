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
  }

  class TodoTest extends TestCase
  {
    // used for testing the tests
    // public function testTodoGet(){
    //   $this->assertTrue(true);
    // }

    protected $app;
    protected $db;

    public function setUp()
    {
      $this->db = $this->createMock('mockDb');
      $this->app = (new nelson\api\App($this->db))->get();
    }

    public function testHelloName() {
    $env = Environment::mock([
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI'    => '/hello/Joe',
        ]);
    $req = Request::createFromEnvironment($env);
    $this->app->getContainer()['request'] = $req;
    $response = $this->app->run(true);
    $this->assertSame($response->getStatusCode(), 200);
    $this->assertSame((string)$response->getBody(), "Hello, Joe");
    }

    // test the GET games endpoint
    public function testGetGames() {

      // expected result string
      $resultString = '[{"id":"1","name":"Goldeneye 64","year":"1997","console":"Nintendo 64"},{"id":"2","name":"Final Fantasy X","year":"2001","console":"Playstation 2"}]';

      // mock the query class & fetchAll functions
      $query = $this->createMock('mockQuery');
      $query->method('fetchAll')
        ->willReturn(json_decode($resultString, true)
      );
       $this->db->method('query')
             ->willReturn($query);

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
      $query->method('fetch')
        ->willReturn(json_decode($resultString, true)
      );
      $this->db->method('query')
          ->willReturn($query);
      $this->db->method('exec')
          ->willReturn(true);

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
  }
