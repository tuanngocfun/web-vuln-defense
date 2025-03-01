<?php
namespace Tests;

require_once __DIR__ . '/../vendor/autoload.php';

use App\Configs\RouteConfig;
use App\Constants\HttpCode;
use App\Core\Dal\DatabaseHandler;
use App\Core\Http\Request\Request;
use App\Core\Http\UploadedFile;
use App\Core\Routing\Contracts\RouteBuilder;
use PHPUnit\Framework\TestCase;

class RouteConfigTest extends TestCase
{
    private $originalRoutes = [];
    private $refactoredRoutes = [];
    private $mockRouteBuilder;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock RouteBuilder that captures all route definitions
        $this->mockRouteBuilder = $this->createMock(RouteBuilder::class);
        
        // Configure the mock to return itself for method chaining
        $methods = ['redirect', 'view', 'get', 'post', 'any', 'prefix', 'controller', 
                   'middleware', 'withoutMiddleware', 'group', 'whereNumber', 'whereIn'];
        
        foreach ($methods as $method) {
            $this->mockRouteBuilder->method($method)
                ->willReturn($this->mockRouteBuilder);
        }
        
        // Set up capture for routes registration
        $this->setupRouteCapture();
    }
    
    private function setupRouteCapture()
    {
        // Override the 'group' method to capture route groups
        $this->mockRouteBuilder->method('group')
            ->will($this->returnCallback(function($routes) {
                if (is_array($routes)) {
                    foreach ($routes as $route) {
                        // Routes are already captured during creation
                    }
                } elseif (is_callable($routes)) {
                    $routes();
                }
                return $this->mockRouteBuilder;
            }));
    }
    
    public function testRoutesEquivalence()
    {
        // Create a reflection of the original class with the old implementation
        $reflectionOriginal = new \ReflectionClass(RouteConfig::class);
        $registerTestingRoutesOriginal = $reflectionOriginal->getMethod('registerTestingRoutes');
        
        // Create a clone of the original for testing
        $originalCode = function($route) {
            $route->prefix('/testdb')->group([
                $route->prefix('/sp')->group([
                    $route->get('/in', function (\App\Core\Dal\DatabaseHandler $db) {
                        $db->execute("DROP TABLE IF EXISTS test");
                        $db->execute("CREATE TABLE test(id INT PRIMARY KEY)");

                        $ids = [1, 2, 3, 4];
                        $db->execute("INSERT INTO test(id) VALUES (?), (?), (?), (?)", ...$ids);
                        
                        $db->execute("DROP PROCEDURE IF EXISTS t");
                        $db->query('CREATE PROCEDURE t(IN step INT) READS SQL DATA
                                    BEGIN
                                        SELECT id FROM test;
                                        SELECT id + step FROM test;
                                    END;
                                    ');
                        
                        $result = $db->callProcedure('t', 10);
                        return response()->json($result);
                    }),
                    $route->get('/in-gen', function (\App\Core\Dal\DatabaseHandler $db) {
                        $db->execute("DROP TABLE IF EXISTS test");
                        $db->execute("CREATE TABLE test(id INT PRIMARY KEY)");

                        $ids = [1, 2, 3];
                        $db->execute("INSERT INTO test(id) VALUES (?), (?), (?)", ...$ids);
                        
                        $db->execute("DROP PROCEDURE IF EXISTS p");
                        $db->query('CREATE PROCEDURE p(IN step INT) READS SQL DATA
                                    BEGIN
                                        SELECT id FROM test;
                                        SELECT id + step FROM test;
                                    END;
                                    ');
                        
                        $result = [];
                        $gen = $db->callProcedureRow('p', 10);
                        foreach ($gen as $rows) {
                            $accu = [];
                            foreach ($rows as $row) {
                                $accu[] = $row;
                            }
                            $result[] = $accu;
                        }
                        return response()->json($result);
                    }),
                    $route->get('/out', function (\App\Core\Dal\DatabaseHandler $db) {
                        $db->execute("DROP TABLE IF EXISTS test");
                        $db->execute("CREATE TABLE test(id INT PRIMARY KEY)");

                        $ids = [[1], [2], [3]];
                        $db->queryMany("INSERT INTO test(id) VALUES (?)", ...$ids);
                        
                        $db->execute("DROP PROCEDURE IF EXISTS p");
                        $db->query('CREATE PROCEDURE p(OUT msg VARCHAR(50))
                                        BEGIN
                                            SELECT "Hi!" INTO msg;
                                        END;'
                                    );
                        
                        $db->execute('SET @foo = "ABC"');
                        $db->execute('CALL p(@foo)');
                        $rows = $db->query('SELECT @foo as _p_out');
                        return response()->json($rows);
                    }),
                ]),
                $route->prefix('/transaction')->group([
                    $route->get('/insert', function (\App\Core\Dal\DatabaseHandler $db) {
                        $db->execute("DROP TABLE IF EXISTS test_transaction");
                        $db->execute("CREATE TABLE test_transaction(id INT PRIMARY KEY)");

                        $db->beginTransaction();
                        try {
                            $db->execute('INSERT INTO test_transaction(id) VALUES (?)', 1);
                            if (!$db->execute('INSERT INTO test_transaction(id) VALUES (?)', 'abc')) {
                                throw new \App\Http\Exceptions\InternalServerErrorException();
                            }
                            $db->commit();
                        }
                        catch (\Exception $e) {
                            $db->rollback();
                        }

                        $result = $db->query('SELECT * FROM test_transaction');
                        return response()->json($result);
                    }),
                ]),
                $route->prefix('/file')->group([
                    $route->get('/init', function (\App\Core\Dal\DatabaseHandler $db) {
                        $db->execute("DROP TABLE IF EXISTS test_file");
                        $db->execute(
                            "CREATE TABLE test_file(
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                filename NVARCHAR(4096),
                                data LONGBLOB
                            )"
                        );
                        return response()->make('Success');
                    }),
                    $route->post('/upload', function (\App\Core\Http\Request\Request $request, \App\Core\Dal\DatabaseHandler $db) {
                        $file = $request->file('my-file');
                        if (!$file) {
                            return response()->make('Missing "my-file" file')->statusCode(HttpCode::BAD_REQUEST);
                        }

                        $filename = $file->getClientOriginalName();
                        $data = $file->getContent();
                        $success = $db->execute(
                            'INSERT INTO test_file(filename, data) VALUES (?, ?)',
                            $filename, $data
                        );

                        return $success ? response()->make('Success') : response()->err(HttpCode::CONFLICT, 'Failed');
                    }),
                    $route->get('/download/{id}', function (\App\Core\Dal\DatabaseHandler $db, int $id) {
                        $rows = $db->query('SELECT filename, data FROM test_file WHERE id = (?)', $id);
                        if (empty($rows)) {
                            return response()->err(HttpCode::NOT_FOUND, 'File Not Found');
                        }
                        $filename = $rows[0]['filename'];
                        $data = $rows[0]['data'];

                        return response()->downloadContent($data, $filename);
                    })->whereNumber('id'),
                    $route->get('/display/{id}', function (\App\Core\Dal\DatabaseHandler $db, int $id) {
                        $rows = $db->query('SELECT data FROM test_file WHERE id = (?)', $id);
                        if (empty($rows)) {
                            return response()->err(HttpCode::NOT_FOUND, 'File Not Found');
                        }
                        $data = $rows[0]['data'];

                        return response()->fileContent($data);
                    })->whereNumber('id'),
                ])
            ]);

            $route->prefix('file')->group([
                $route->get('download', fn() => response()->download(RouteConfig::$filename)),
                $route->get('display', fn() => response()->file(RouteConfig::$filename)),
                $route->post('up-save-download', function (Request $request) {
                    $file = $request->file('my-file');
                    if (!$file || !$file->isValid()) {
                        return response()->make('Missing or invalid file')->statusCode(HttpCode::BAD_REQUEST);
                    }

                    $storedFilename = $file->store('public/assets/uploads');
                    if (!$storedFilename) {
                        return response()->err(HttpCode::CONFLICT, 'Unable to save file');
                    }

                    return response()->download($storedFilename);
                }),
                $route->post('up-save-display', function (Request $request) {
                    $file = $request->file('my-file');
                    if (!$file || !$file->isValid()) {
                        return response()->make('Missing or invalid file')->statusCode(HttpCode::BAD_REQUEST);
                    }

                    $storedFilename = $file->store('public/assets/uploads');
                    if (!$storedFilename) {
                        return response()->err(HttpCode::CONFLICT, 'Unable to save file');
                    }

                    return response()->file($storedFilename);
                }),
            ]);

            $route->prefix('image')->group([
                $route->get('download', fn() => response()->download(RouteConfig::$imageName)),
                $route->get('display', fn() => response()->file(RouteConfig::$imageName)),
            ]);
        };
        
        // Track route calls
        $originalRouteCalls = [];
        $refactoredRouteCalls = [];
        
        // Create a proxy to track original route calls
        $originalProxy = new class($this->mockRouteBuilder, $originalRouteCalls) {
            private $routeBuilder;
            private $calls;
            
            public function __construct($routeBuilder, &$calls) {
                $this->routeBuilder = $routeBuilder;
                $this->calls = &$calls;
            }
            
            public function __call($name, $arguments) {
                $this->calls[] = ['method' => $name, 'args' => $arguments];
                return $this->routeBuilder;
            }
        };
        
        // Execute original code with tracking
        $originalCode($originalProxy);
        
        // Now execute refactored code
        $refactoredProxy = new class($this->mockRouteBuilder, $refactoredRouteCalls) {
            private $routeBuilder;
            private $calls;
            
            public function __construct($routeBuilder, &$calls) {
                $this->routeBuilder = $routeBuilder;
                $this->calls = &$calls;
            }
            
            public function __call($name, $arguments) {
                $this->calls[] = ['method' => $name, 'args' => $arguments];
                return $this->routeBuilder;
            }
        };
        
        // Execute refactored code
        RouteConfig::registerTestingRoutes($refactoredProxy);
        
        // Convert callbacks to strings for comparison (since we can't directly compare closures)
        $this->normalizeCalls($originalRouteCalls);
        $this->normalizeCalls($refactoredRouteCalls);
        
        // Assert that both implementations generate the same route structure
        $this->assertEquals(
            $this->sortAndNormalize($originalRouteCalls),
            $this->sortAndNormalize($refactoredRouteCalls),
            "The refactored code should generate the same route structure as the original"
        );
    }
    
    private function normalizeCalls(&$calls)
    {
        foreach ($calls as &$call) {
            foreach ($call['args'] as &$arg) {
                if ($arg instanceof \Closure) {
                    $arg = 'Closure-' . spl_object_hash($arg);
                }
            }
        }
    }
    
    private function sortAndNormalize($calls)
    {
        // Extract all route paths and normalize them
        $routePaths = [];
        foreach ($calls as $call) {
            if ($call['method'] === 'get' || $call['method'] === 'post') {
                $path = $call['args'][0] ?? null;
                if ($path !== null) {
                    $routePaths[] = $path;
                }
            }
        }
        
        sort($routePaths);
        return $routePaths;
    }
    
    public function testRouteFunctionalityEquivalence()
    {
        // Test specific functionalities to ensure they behave the same way
        
        // Create mock objects needed for route handlers
        $dbHandler = $this->createMock(\App\Core\Dal\DatabaseHandler::class);
        $request = $this->createMock(\App\Core\Http\Request\Request::class);
        
        // Mock file object
        $file = $this->createMock(\App\Core\Http\UploadedFile::class);
        
        // Test database initialization sequence
        $dbHandler->expects($this->exactly(2))
                 ->method('execute')
                 ->withConsecutive(
                     [$this->equalTo("DROP TABLE IF EXISTS test")],
                     [$this->equalTo("CREATE TABLE test(id INT PRIMARY KEY)")]
                 );
        
        // Extract and compare the SQL statements in both implementations
        $dbStatements = $this->extractSqlStatements(RouteConfig::class);
        $this->assertContains("DROP TABLE IF EXISTS test", $dbStatements);
        $this->assertContains("CREATE TABLE test(id INT PRIMARY KEY)", $dbStatements);
        
        // Verify that constants are used in the refactored code
        $refClass = new \ReflectionClass(RouteConfig::class);
        $constants = $refClass->getConstants();
        
        $this->assertArrayHasKey('SQL_DROP_TEST_TABLE', $constants);
        $this->assertEquals("DROP TABLE IF EXISTS test", $constants['SQL_DROP_TEST_TABLE']);
        
        $this->assertArrayHasKey('SQL_CREATE_TEST_TABLE', $constants);
        $this->assertEquals("CREATE TABLE test(id INT PRIMARY KEY)", $constants['SQL_CREATE_TEST_TABLE']);
    }
    
    private function extractSqlStatements($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        $statements = [];
        
        foreach ($reflectionClass->getMethods() as $method) {
            $source = file_get_contents($reflectionClass->getFileName());
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            
            $methodSource = implode("\n", array_slice(
                explode("\n", $source), 
                $startLine - 1, 
                $endLine - $startLine + 1
            ));
            
            // Extract SQL strings (this is a simplified approach)
            preg_match_all('/["\'](DROP|CREATE|INSERT|SELECT|UPDATE|DELETE).*?["\']/', 
                          $methodSource, 
                          $matches);
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    // Clean up the quotes
                    $statement = trim($match, '\'"');
                    $statements[] = $statement;
                }
            }
        }
        
        return $statements;
    }
}
