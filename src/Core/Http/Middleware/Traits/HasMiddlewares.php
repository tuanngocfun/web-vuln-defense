<?php
namespace App\Core\Http\Middleware\Traits;

use App\Core\Http\Middleware\Middleware;
use App\Utils\Arrays;
use App\Utils\Reflections;

/**
 * Trait HasMiddlewares
 *
 * @namespace App\Core\Http\Middleware\Traits
 * @description
 * Trait này cung cấp khả năng gán một hoặc nhiều middleware cho một route hoặc một controller.
 * Điều này giúp quản lý middleware một cách dễ dàng, linh hoạt hơn khi sử dụng trong các class controller.
 *
 * @example
 * ```php
 * use App\Core\Http\Middleware\Traits\HasMiddlewares;
 *
 * class MyController {
 *     use HasMiddlewares;
 *
 *     public function handleRequest() {
 *         $this->middleware('AuthMiddleware');
 *         $this->middleware(['CsrfMiddleware', 'RateLimitMiddleware']);
 *     }
 * }
 * ```
 */
trait HasMiddlewares
{
    /**
     * @var ?string[]
     */
    protected ?array $middlewares = null;

    /**
     * Gán middleware cho đối tượng.
     *
     * @param string|array $middleware Tên hoặc danh sách middleware cần gán.
     * @return self Trả về chính đối tượng để hỗ trợ method chaining.
     *
     * @description
     * Phương thức này cho phép các lớp sử dụng trait đăng ký middleware trước khi xử lý request.
     * Hệ thống middleware có thể kiểm tra danh sách này để thực thi middleware tương ứng.
     *
     * @example
     * ```php
     * $controller->middleware('AuthMiddleware');
     * $controller->middleware(['CsrfMiddleware', 'LoggingMiddleware']);
     * ```
     */
    public function middleware(string|array $middleware): self
    {
        $this->middlewares = Arrays::asArray($middleware);
        return $this;
    }
}
