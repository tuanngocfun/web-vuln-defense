<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Configs\RouteConfig;
use App\Core\Routing\DelegatingRouter;
use App\Core\Routing\RouteResolvedResult;
use App\Http\Controllers\MemeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Core\Http\Request\Request;

class RouteConfigTest extends TestCase
{
    /**
     * @var DelegatingRouter
     */
    private $router;

    protected function setUp(): void
    {
        // Create a new router instance and register all routes.
        $this->router = new DelegatingRouter();
        RouteConfig::register($this->router);
    }

    public function testRedirectRoot(): void
    {
        // The root route ('/') is set to redirect to '/home'
        $result = $this->router->resolve('/', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Root should resolve to a RouteResolvedResult');
        // We can check that an action exists (likely a closure that performs redirection)
        $action = $result->action();
        $this->assertTrue(is_callable($action) || is_array($action), 'Action must be callable or array');
    }

    public function testHomeRoute(): void
    {
        $result = $this->router->resolve('/home', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Home route should resolve correctly');
        // This view route should return the "home" view.
        $action = $result->action();
        $this->assertTrue(is_callable($action) || is_array($action), 'Home route action must be callable or array');
    }

    public function testInfoRoute(): void
    {
        $result = $this->router->resolve('/info', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Info route should resolve');
        // Action is a closure that calls phpinfo().
        $this->assertTrue(is_callable($result->action()), 'Info route action must be callable');
    }

    public function testEnvironmentRoute(): void
    {
        $result = $this->router->resolve('/environment', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Environment route should resolve');
    }

    public function testApiParamRoute(): void
    {
        $result = $this->router->resolve('/api/param-test', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'API param route should resolve');
        $params = $result->routeParams();
        $this->assertArrayHasKey('param', $params, 'Route params should include "param"');
        $this->assertEquals('test', $params['param'], 'Param should equal "test"');
    }

    public function testApiParamWithIdRoute(): void
    {
        $result = $this->router->resolve('/api/something/123', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'API route with id should resolve');
        $params = $result->routeParams();
        $this->assertArrayHasKey('param', $params, 'Route params should include "param"');
        $this->assertArrayHasKey('id', $params, 'Route params should include "id"');
        $this->assertEquals('something', $params['param'], 'Param should equal "something"');
        $this->assertEquals('123', $params['id'], 'ID should equal "123"');
    }

    public function testExampleHelloRoute(): void
    {
        $result = $this->router->resolve('/example/hello', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Example/hello route should resolve');
    }

    public function testExampleHelloNameRoute(): void
    {
        $result = $this->router->resolve('/example/hello/john', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Example/hello/{name} route should resolve');
        $params = $result->routeParams();
        $this->assertArrayHasKey('name', $params, 'Route params should include "name"');
        $this->assertEquals('john', $params['name'], 'Name param should be "john"');
    }

    public function testMemeRouteClosure(): void
    {
        // Route defined as: /meme-{animal} with whereIn('animal', ['cat', 'dog'])
        $result = $this->router->resolve('/meme-cat', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Meme route (closure) should resolve');
        $params = $result->routeParams();
        $this->assertArrayHasKey('animal', $params, 'Route params should include "animal"');
        $this->assertEquals('cat', $params['animal'], 'Animal should equal "cat"');
    }

    public function testMemeGroupRoute(): void
    {
        // Grouped route under /meme with controller MemeController
        $result = $this->router->resolve('/meme/cat', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Meme group route should resolve');
        $action = $result->action();
        $this->assertIsArray($action, 'Meme group route action should be an array');
        $this->assertEquals(MemeController::class, $action[0], 'Controller should be MemeController');
        $this->assertEquals('showCat', $action[1], 'Action method should be showCat');
    }

    public function testJsonRoute(): void
    {
        $result = $this->router->resolve('/json', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'JSON route should resolve');
        $action = $result->action();
        $this->assertTrue(is_callable($action), 'JSON route action must be callable');
    }

    public function testDashboardRoute(): void
    {
        $result = $this->router->resolve('/dashboard', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Dashboard route should resolve');
        $action = $result->action();
        $this->assertIsArray($action, 'Dashboard route action should be an array');
        $this->assertEquals(DashboardController::class, $action[0], 'Dashboard controller must be DashboardController');
        $this->assertEquals('show', $action[1], 'Dashboard action must be "show"');
    }

    public function testAuthRoutes(): void
    {
        $loginResult = $this->router->resolve('/auth/login', 'POST');
        $logoutResult = $this->router->resolve('/auth/logout', 'POST');
        $tokenResult = $this->router->resolve('/auth/token', 'POST');
        $this->assertInstanceOf(RouteResolvedResult::class, $loginResult, 'Auth login route should resolve');
        $this->assertInstanceOf(RouteResolvedResult::class, $logoutResult, 'Auth logout route should resolve');
        $this->assertInstanceOf(RouteResolvedResult::class, $tokenResult, 'Auth token route should resolve');

        $loginAction = $loginResult->action();
        $this->assertIsArray($loginAction, 'Auth login action should be an array');
        $this->assertEquals(AuthController::class, $loginAction[0], 'Auth controller must be AuthController');
        $this->assertEquals('login', $loginAction[1], 'Auth login method must be "login"');
    }

    public function testProtectedRoutes(): void
    {
        // Protected routes are grouped under middleware 'auth'
        $resultUsers = $this->router->resolve('/users', 'GET');
        $resultMessages = $this->router->resolve('/messages', 'GET');

        $this->assertInstanceOf(RouteResolvedResult::class, $resultUsers, 'Users route should resolve');
        $this->assertInstanceOf(RouteResolvedResult::class, $resultMessages, 'Messages route should resolve');
    }

    public function testNotFoundRoute(): void
    {
        // A route that does not match any defined route should resolve to the fallback route
        $result = $this->router->resolve('/nonexistent-route', 'GET');
        $this->assertInstanceOf(RouteResolvedResult::class, $result, 'Fallback route should resolve');
        $action = $result->action();
        $this->assertTrue(is_callable($action), 'Fallback route action must be callable');
    }
}
