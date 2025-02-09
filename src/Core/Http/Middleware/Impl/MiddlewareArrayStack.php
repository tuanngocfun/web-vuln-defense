<?php
namespace App\Core\Http\Middleware\Impl;

use App\Core\Di\Exceptions\CycleDetectedException;
use App\Core\Http\Middleware\ErrorMiddleware;
use App\Core\Http\Middleware\Middleware;
use App\Core\Http\Middleware\MiddlewareStack;
use App\Support\Collection\ArrayMultiMap;
use App\Support\Collection\MultiMap;
use App\Utils\Arrays;
use App\Utils\Reflections;

/**
 * Lớp MiddlewareArrayStack quản lý danh sách middleware trong hệ thống HTTP middleware.
 *
 * @namespace App\Core\Http\Middleware\Impl
 * @description
 * Lớp này triển khai giao diện `MiddlewareStack`, giúp quản lý và tổ chức middleware theo thứ tự ưu tiên.
 * Nó hỗ trợ gán tên cho middleware, quản lý lỗi middleware, và ngăn chặn các vòng lặp khi gán middleware.
 *
 * @example
 * ```php
 * use App\Core\Http\Middleware\Impl\MiddlewareArrayStack;
 *
 * $stack = new MiddlewareArrayStack(App\Core\Http\Middleware\Impl\DefaultErrorMiddleware::class);
 * $stack->use([AuthMiddleware::class, LoggingMiddleware::class]);
 * $stack->append(CacheMiddleware::class);
 * ```
 */
class MiddlewareArrayStack implements MiddlewareStack
{
    /**
     * Danh sách middleware theo thứ tự thực thi.
     *
     * @var string[]
     */
    private array $middlewares;

    /**
     * Danh sách middleware được đặt tên.
     *
     * @var MultiMap
     */
    private MultiMap $namedMiddlewares;

    private string $errorMiddleware;

    public function __construct(string $errorMiddleware) {
        $this->middlewares = [];
        $this->namedMiddlewares = new ArrayMultiMap();
        $this->errorMiddleware = Reflections::ensureValidImplementation($errorMiddleware, ErrorMiddleware::class);
    }

    #[\Override]
    public function getMiddlewares(): array {
        return $this->middlewares;
    }

    /**
     * Lấy danh sách middleware theo tên.
     *
     * @param string $name Tên của middleware.
     * @return array|false Danh sách middleware hoặc `false` nếu không tồn tại.
     */
    #[\Override]
    public function getMiddlewaresByName(string $name): array|false {
        if (!$this->namedMiddlewares->contains($name)) {
            return false;
        }
        return $this->getMiddlewaresByNameImpl($name, []);
    }

    /**
     * Đệ quy lấy middleware theo tên, kiểm tra vòng lặp trong middleware.
     *
     * @param string $name Tên middleware.
     * @param array $finding Danh sách middleware đang tìm kiếm (để phát hiện vòng lặp).
     * @return array Danh sách middleware được ánh xạ.
     * @throws CycleDetectedException Nếu phát hiện vòng lặp middleware.
     */
    private function getMiddlewaresByNameImpl(string $name, array $finding) {
        $finding[$name] = true;

        $middlewares = $this->namedMiddlewares->get($name);
        if ($middlewares === false) {
            return [$name];
        }

        $result = [];
        foreach ($middlewares as $middleware) {
            if (isset($finding[$middleware])) {
                throw new CycleDetectedException("A cycle detected when trying to resolve middleware [$name]");
            }
            $resolvedMiddlewares = $this->getMiddlewaresByNameImpl($middleware, $finding);
            array_push($result, ...$resolvedMiddlewares);
        }
        return $result;
    }

    #[\Override]
    public function getErrorMiddleware(): string {
        return $this->errorMiddleware;
    }

    /**
     * Gán middleware vào tên cụ thể.
     *
     * @param string $name Tên middleware.
     * @param string|array $middleware Middleware cần gán.
     * @return static Trả về chính đối tượng để hỗ trợ method chaining.
     */
    #[\Override]
    public function assignName(string $name, string|array $middleware): static {
        $givenMiddlewares = Arrays::asArray($middleware);
        $values = [];
        foreach ($givenMiddlewares as $middleware) {
            $middlewares = $this->getMiddlewaresByName($middleware);
            if ($middlewares === false) {
                $middlewares = Arrays::asArray($middleware);
                $middlewares = Reflections::ensureValidImplementations($middlewares, Middleware::class);
            }
            array_push($values, ...$middlewares);
        }
        $this->namedMiddlewares->set($name, $values);
        return $this;
    }

    /**
     * Đăng ký danh sách middleware vào stack.
     *
     * @param array $middlewares Danh sách middleware cần đăng ký.
     * @return static Trả về chính đối tượng để hỗ trợ method chaining.
     */
    #[\Override]
    public function use(array $middlewares): static {
        foreach ($middlewares as $key => $value) {
            $values = $this->getMiddlewaresByName($value);
            if ($values === false) {
                $values = Arrays::asArray($value);
                $values = Reflections::ensureValidImplementations($values, Middleware::class);
                if (is_string($key)) {
                    $this->namedMiddlewares->set($key, $values);
                }
            }
            array_push($this->middlewares, ...$values);
        }
        return $this;
    }

    /**
     * Đặt middleware xử lý lỗi.
     *
     * @param string $errorMiddleware Middleware xử lý lỗi.
     * @return static Trả về chính đối tượng để hỗ trợ method chaining.
     */

    #[\Override]
    public function useError(string $errorMiddleware): static {
        $this->errorMiddleware = Reflections::ensureValidImplementation($errorMiddleware, ErrorMiddleware::class);
        return $this;
    }

    /**
     * Thêm middleware vào cuối stack.
     *
     * @param string $middleware Middleware cần thêm.
     * @param ?string $name (Tùy chọn) Tên middleware.
     * @return static Trả về chính đối tượng để hỗ trợ method chaining.
     */
    #[\Override]
    public function append(string $middleware, ?string $name = null): static {
        $this->middlewares[] = Reflections::ensureValidImplementation($middleware, Middleware::class);
        if ($name !== null) {
            $this->namedMiddlewares->putIfAbsent($name, $middleware);
        }
        return $this;
    }
    
    /**
     * Thêm middleware vào đầu stack.
     *
     * @param string $middleware Middleware cần thêm.
     * @param ?string $name (Tùy chọn) Tên middleware.
     * @return static Trả về chính đối tượng để hỗ trợ method chaining.
     */
    #[\Override]
    public function prepend(string $middleware, ?string $name = null): static {
        array_unshift($this->middlewares, Reflections::ensureValidImplementation($middleware, Middleware::class));
        if ($name !== null) {
            $this->namedMiddlewares->putIfAbsent($name, $middleware);
        }
        return $this;
    }
}
