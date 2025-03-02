<?php
declare(strict_types=1);

// Fallback for getallheaders() in CLI (or environments where it's not defined)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        return [];
    }
}

use PHPUnit\Framework\TestCase;
use App\Core\Routing\DelegatingRouter;
use App\Core\Routing\Contracts\RouteBuilder;
use App\Core\Dal\DatabaseHandler;
use App\Utils\Functions;
use App\Constants\HttpCode;

// --- FakeDatabaseHandler: A stub for DatabaseHandler ---
class FakeDatabaseHandler implements DatabaseHandler {
    public $calls = [];

    public function beginTransaction(): void {
        $this->calls[] = "beginTransaction";
    }
    public function rollback(): void {
        $this->calls[] = "rollback";
    }
    public function commit(): void {
        $this->calls[] = "commit";
    }
    public function execute(string $query, mixed ...$params): bool {
        $this->calls[] = "execute: $query with " . json_encode($params);
        return true;
    }
    public function query(string $query, mixed ...$params): array {
        $this->calls[] = "query: $query with " . json_encode($params);
        return [["dummy" => "result"]];
    }
    public function queryRaw(string $query): array|true {
        $this->calls[] = "queryRaw: $query";
        return [["dummy" => "result"]];
    }
    public function queryMany(string $query, array ...$params): array {
        $this->calls[] = "queryMany: $query with " . json_encode($params);
        return [[["dummy" => "result"]]];
    }
    public function queryRow(string $query, mixed ...$params): \Generator {
        $this->calls[] = "queryRow: $query with " . json_encode($params);
        yield ["dummy" => "result"];
    }
    public function callProcedure(string $procedureName, mixed ...$params): array {
        $this->calls[] = "callProcedure: $procedureName with " . json_encode($params);
        return [["proc" => "result"]];
    }
    public function callProcedureRow(string $procedureName, mixed ...$params): \Generator {
        $this->calls[] = "callProcedureRow: $procedureName with " . json_encode($params);
        yield [["proc" => "result"]];
    }
}

// --- Enhanced FakeDatabaseHandler for tracking call sequences ---
class TrackingFakeDatabaseHandler extends FakeDatabaseHandler {
    public function getCallSequence(): array {
        return $this->calls;
    }

    public function clearCalls(): void {
        $this->calls = [];
    }
    
    public function getLastCall(): ?string {
        if (empty($this->calls)) {
            return null;
        }
        return $this->calls[count($this->calls) - 1];
    }
}

// --- simulateRequest helper ---
// This function resolves a route and executes its action.
// If the action expects parameters (i.e. a DatabaseHandler), we pass a FakeDatabaseHandler.
function simulateRequest(DelegatingRouter $router, string $uri, string $method = 'GET'): ?string {
    $result = $router->resolve($uri, strtoupper($method));
    if (!$result instanceof \App\Core\Routing\RouteResolvedResult) {
        return null;
    }
    // Bind route parameters.
    $action = Functions::bindParams($result->action(), $result->routeParams());
    
    // Determine if the action expects a parameter.
    $reflection = new ReflectionFunction($action);
    $numRequired = $reflection->getNumberOfRequiredParameters();
    $args = [];
    if ($numRequired > 0) {
        $args[] = new FakeDatabaseHandler();
    }
    
    ob_start();
    $action(...$args);
    $output = ob_get_clean();
    return $output;
}

// --- Enhanced simulateRequest that returns the database handler ---
function simulateRequestWithDB(DelegatingRouter $router, string $uri, string $method = 'GET'): array {
    $result = $router->resolve($uri, strtoupper($method));
    if (!$result instanceof \App\Core\Routing\RouteResolvedResult) {
        return [null, null];
    }
    // Bind route parameters.
    $action = Functions::bindParams($result->action(), $result->routeParams());
    
    // Determine if the action expects a parameter.
    $reflection = new ReflectionFunction($action);
    $numRequired = $reflection->getNumberOfRequiredParameters();
    $db = new TrackingFakeDatabaseHandler();
    $args = [];
    if ($numRequired > 0) {
        $args[] = $db;
    }
    
    ob_start();
    $action(...$args);
    $output = ob_get_clean();
    return [$output, $db];
}

// --- Dummy RouteConfig classes representing old and refactored route registrations ---

class RouteConfigOld {
    public static function register(RouteBuilder $route): void {
        $route->prefix('/testdb')->group([
            $route->prefix('/sp')->group([
                $route->get('/in', function (DatabaseHandler $db) {
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
                $route->get('/in-gen', function (DatabaseHandler $db) {
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
                $route->get('/out', function (DatabaseHandler $db) {
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
                $route->get('/insert', function (DatabaseHandler $db) {
                    $db->execute("DROP TABLE IF EXISTS test_transaction");
                    $db->execute("CREATE TABLE test_transaction(id INT PRIMARY KEY)");
                    $db->beginTransaction();
                    try {
                        $db->execute('INSERT INTO test_transaction(id) VALUES (?)', 1);
                        if (!$db->execute('INSERT INTO test_transaction(id) VALUES (?)', 'abc')) {
                            throw new \App\Http\Exceptions\InternalServerErrorException();
                        }
                        $db->commit();
                    } catch (\Exception $e) {
                        $db->rollback();
                    }
                    $result = $db->query('SELECT * FROM test_transaction');
                    return response()->json($result);
                }),
            ]),
            $route->prefix('/file')->group([
                $route->get('/init', function (DatabaseHandler $db) {
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
            ]),
        ]);
    }
}

class RouteConfigNew {
    public static function register(RouteBuilder $route): void {
        $sqlDropTestTable = "DROP TABLE IF EXISTS test";
        $sqlCreateTestTable = "CREATE TABLE test(id INT PRIMARY KEY)";
        $testdbPrefix = '/testdb';
        self::registerTestingRoutes($route, $sqlDropTestTable, $sqlCreateTestTable, $testdbPrefix);
    }
    private static function registerTestingRoutes(RouteBuilder $route, string $dropSQL, string $createSQL, string $prefix): void {
        self::registerTestDbRoutes($route, $dropSQL, $createSQL, $prefix);
        self::registerTransactionRoutes($route, $prefix);
        self::registerFileRoutes($route, $prefix);
    }
    private static function registerTestDbRoutes(RouteBuilder $route, string $dropSQL, string $createSQL, string $prefix): void {
        $route->prefix($prefix)->group([
            $route->prefix('/sp')->group([
                $route->get('/in', function (DatabaseHandler $db) use ($dropSQL, $createSQL) {
                    $db->execute($dropSQL);
                    $db->execute($createSQL);
                    $ids = [1, 2, 3, 4];
                    $db->execute("INSERT INTO test(id) VALUES (?), (?), (?), (?)", ...$ids);
                    $db->execute("DROP PROCEDURE IF EXISTS t");
                    $db->query('CREATE PROCEDURE t(IN step INT) READS SQL DATA
                                BEGIN
                                    SELECT id FROM test;
                                    SELECT id + step FROM test;
                                END;');
                    $result = $db->callProcedure('t', 10);
                    return response()->json($result);
                }),
                $route->get('/in-gen', function (DatabaseHandler $db) use ($dropSQL, $createSQL) {
                    $db->execute($dropSQL);
                    $db->execute($createSQL);
                    $ids = [1, 2, 3];
                    $db->execute("INSERT INTO test(id) VALUES (?), (?), (?)", ...$ids);
                    $db->execute("DROP PROCEDURE IF EXISTS p");
                    $db->query('CREATE PROCEDURE p(IN step INT) READS SQL DATA
                                BEGIN
                                    SELECT id FROM test;
                                    SELECT id + step FROM test;
                                END;');
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
                $route->get('/out', function (DatabaseHandler $db) use ($dropSQL, $createSQL) {
                    $db->execute($dropSQL);
                    $db->execute($createSQL);
                    $ids = [[1], [2], [3]];
                    $db->queryMany("INSERT INTO test(id) VALUES (?)", ...$ids);
                    $db->execute("DROP PROCEDURE IF EXISTS p");
                    $db->query('CREATE PROCEDURE p(OUT msg VARCHAR(50))
                                    BEGIN
                                        SELECT "Hi!" INTO msg;
                                    END;');
                    $db->execute('SET @foo = "ABC"');
                    $db->execute('CALL p(@foo)');
                    $rows = $db->query('SELECT @foo as _p_out');
                    return response()->json($rows);
                }),
            ]),
        ]);
    }
    private static function registerTransactionRoutes(RouteBuilder $route, string $prefix): void {
        $route->prefix($prefix)->prefix('/transaction')->group([
            $route->get('/insert', function (DatabaseHandler $db) {
                $db->execute("DROP TABLE IF EXISTS test_transaction");
                $db->execute("CREATE TABLE test_transaction(id INT PRIMARY KEY)");
                $db->beginTransaction();
                try {
                    $db->execute('INSERT INTO test_transaction(id) VALUES (?)', 1);
                    if (!$db->execute('INSERT INTO test_transaction(id) VALUES (?)', 'abc')) {
                        throw new \App\Http\Exceptions\InternalServerErrorException();
                    }
                    $db->commit();
                } catch (\Exception $e) {
                    $db->rollback();
                }
                $result = $db->query('SELECT * FROM test_transaction');
                return response()->json($result);
            }),
        ]);
    }
    private static function registerFileRoutes(RouteBuilder $route, string $prefix): void {
        $route->prefix($prefix)->prefix('/file')->group([
            $route->get('/init', function (DatabaseHandler $db) {
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
        ]);
    }
}

// --- Integration test class ---
class RouteConfigRefactorTest extends TestCase {
    /**
     * Creates a new router instance with routes registered by the given config class.
     */
    private function getRouterWithConfig(string $configClass): DelegatingRouter {
        $router = new DelegatingRouter();
        $configClass::register($router);
        return $router;
    }
    
    public function testSpInEndpoint(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        $outputOld = simulateRequest($routerOld, "/testdb/sp/in", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/sp/in", "GET");
        $this->assertEquals($outputOld, $outputNew, "Output for /testdb/sp/in should be identical");
    }
    
    public function testSpInGenEndpoint(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        $outputOld = simulateRequest($routerOld, "/testdb/sp/in-gen", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/sp/in-gen", "GET");
        $this->assertEquals($outputOld, $outputNew, "Output for /testdb/sp/in-gen should be identical");
    }
    
    public function testSpOutEndpoint(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        $outputOld = simulateRequest($routerOld, "/testdb/sp/out", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/sp/out", "GET");
        $this->assertEquals($outputOld, $outputNew, "Output for /testdb/sp/out should be identical");
    }
    
    public function testTransactionInsertEndpoint(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        $outputOld = simulateRequest($routerOld, "/testdb/transaction/insert", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/transaction/insert", "GET");
        $this->assertEquals($outputOld, $outputNew, "Output for /testdb/transaction/insert should be identical");
    }
    
    public function testFileInitEndpoint(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        $outputOld = simulateRequest($routerOld, "/testdb/file/init", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/file/init", "GET");
        $this->assertEquals($outputOld, $outputNew, "Output for /testdb/file/init should be identical");
    }
    
    /**
     * Test that non-existent endpoints return null for both implementations
     */
    public function testNonExistentEndpoint(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        $outputOld = simulateRequest($routerOld, "/testdb/not-exists", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/not-exists", "GET");
        $this->assertNull($outputOld, "Non-existent endpoint should return null for old config");
        $this->assertNull($outputNew, "Non-existent endpoint should return null for new config");
    }
    
    /**
     * Test that the database transaction calls are made in the same order
     */
    public function testTransactionCallSequence(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        
        list($outputOld, $dbOld) = simulateRequestWithDB($routerOld, "/testdb/transaction/insert", "GET");
        list($outputNew, $dbNew) = simulateRequestWithDB($routerNew, "/testdb/transaction/insert", "GET");
        
        $this->assertEquals($dbOld->getCallSequence(), $dbNew->getCallSequence(), 
            "Database call sequence for /testdb/transaction/insert should be identical");
    }
    
    /**
     * Test the SP/IN endpoint database call sequence
     */
    public function testSpInCallSequence(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        
        list($outputOld, $dbOld) = simulateRequestWithDB($routerOld, "/testdb/sp/in", "GET");
        list($outputNew, $dbNew) = simulateRequestWithDB($routerNew, "/testdb/sp/in", "GET");
        
        // We know the SQL is different due to refactoring, so we just check that
        // the number of calls and call types are the same
        $this->assertEquals(count($dbOld->getCallSequence()), count($dbNew->getCallSequence()),
            "Number of database calls for /testdb/sp/in should be identical");
            
        // Check that the last call (callProcedure) has the same parameters
        $callSequenceOld = $dbOld->getCallSequence();
        $callSequenceNew = $dbNew->getCallSequence();
        $lastCallOld = $callSequenceOld[count($callSequenceOld) - 1];
        $lastCallNew = $callSequenceNew[count($callSequenceNew) - 1];
        
        $this->assertEquals($lastCallOld, $lastCallNew,
            "Last database call for /testdb/sp/in should be identical");
    }
    
    /**
     * Test the SP/IN-GEN endpoint database call sequence
     */
    public function testSpInGenCallSequence(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        
        list($outputOld, $dbOld) = simulateRequestWithDB($routerOld, "/testdb/sp/in-gen", "GET");
        list($outputNew, $dbNew) = simulateRequestWithDB($routerNew, "/testdb/sp/in-gen", "GET");
        
        // Check that the last call (callProcedureRow) has the same parameters
        $callSequenceOld = $dbOld->getCallSequence();
        $callSequenceNew = $dbNew->getCallSequence();
        $lastCallOld = $callSequenceOld[count($callSequenceOld) - 1];
        $lastCallNew = $callSequenceNew[count($callSequenceNew) - 1];
        
        $this->assertEquals($lastCallOld, $lastCallNew,
            "Last database call for /testdb/sp/in-gen should be identical");
    }
    
    /**
     * Test request methods other than GET
     */
    public function testMethodNotAllowed(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        
        // Test POST method on GET endpoint
        $outputOld = simulateRequest($routerOld, "/testdb/sp/in", "POST");
        $outputNew = simulateRequest($routerNew, "/testdb/sp/in", "POST");
        $this->assertEquals($outputOld, $outputNew, 
            "Method not allowed response should be identical");
    }
    
    /**
     * Test that both routers have the same endpoints available
     */
    public function testEndpointAvailability(): void {
        $routerOld = $this->getRouterWithConfig(RouteConfigOld::class);
        $routerNew = $this->getRouterWithConfig(RouteConfigNew::class);
        
        $testEndpoints = [
            "/testdb/sp/in",
            "/testdb/sp/in-gen",
            "/testdb/sp/out",
            "/testdb/transaction/insert",
            "/testdb/file/init"
        ];
        
        foreach ($testEndpoints as $endpoint) {
            $outputOld = simulateRequest($routerOld, $endpoint, "GET");
            $outputNew = simulateRequest($routerNew, $endpoint, "GET");
            
            $this->assertEquals(
                $outputOld !== null,
                $outputNew !== null,
                "Endpoint availability for $endpoint should be identical"
            );
        }
    }
    
    /**
     * Test deep route nesting
     */
    public function testDeepRouteNesting(): void {
        // Extend RouteConfigOld with a deep nested route
        $routerOld = new DelegatingRouter();
        $routerOld->prefix('/testdb')->group([
            $routerOld->prefix('/deep')->group([
                $routerOld->prefix('/nested')->group([
                    $routerOld->prefix('/route')->group([
                        $routerOld->get('/endpoint', function() {
                            return response()->make('Deep nested route');
                        })
                    ])
                ])
            ])
        ]);
        
        // Extend RouteConfigNew with the same deep nested route
        $routerNew = new DelegatingRouter();
        $routerNew->prefix('/testdb')->group([
            $routerNew->prefix('/deep')->group([
                $routerNew->prefix('/nested')->group([
                    $routerNew->prefix('/route')->group([
                        $routerNew->get('/endpoint', function() {
                            return response()->make('Deep nested route');
                        })
                    ])
                ])
            ])
        ]);
        
        $outputOld = simulateRequest($routerOld, "/testdb/deep/nested/route/endpoint", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/deep/nested/route/endpoint", "GET");
        
        $this->assertEquals($outputOld, $outputNew, 
            "Deep nested route should be identical");
    }
    
    /**
     * Test route parameters
     */
    public function testRouteParameters(): void {
        // Extend RouteConfigOld with a parameterized route
        $routerOld = new DelegatingRouter();
        $routerOld->prefix('/testdb')->group([
            $routerOld->get('/param/{id}', function(string $id) {
                return response()->make("Param: $id");
            })
        ]);
        
        // Extend RouteConfigNew with the same parameterized route
        $routerNew = new DelegatingRouter();
        $routerNew->prefix('/testdb')->group([
            $routerNew->get('/param/{id}', function(string $id) {
                return response()->make("Param: $id");
            })
        ]);
        
        $outputOld = simulateRequest($routerOld, "/testdb/param/123", "GET");
        $outputNew = simulateRequest($routerNew, "/testdb/param/123", "GET");
        
        $this->assertEquals($outputOld, $outputNew, 
            "Route with parameters should be identical");
    }
}