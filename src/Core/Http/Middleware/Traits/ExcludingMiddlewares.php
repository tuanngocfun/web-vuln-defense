<?php
namespace App\Core\Http\Middleware\Traits;

use App\Core\Http\Middleware\Middleware;
use App\Utils\Arrays;
use App\Utils\Reflections;

/**
 * Trait ExcludingMiddlewares
 *
 * @namespace App\Core\Http\Middleware\Traits
 * @description
 * Trait này cung cấp khả năng loại bỏ (exclude) một hoặc nhiều middleware khỏi quá trình xử lý.
 * Điều này hữu ích khi bạn muốn tạm thời vô hiệu hóa một middleware cụ thể cho một route hoặc một controller.
 *
 * @example
 * ```php
 * use App\Core\Http\Middleware\Traits\ExcludingMiddlewares;
 *
 * class MyController {
 *     use ExcludingMiddlewares;
 *
 *     public function handleRequest() {
 *         $this->withoutMiddleware(['AuthMiddleware', 'CsrfMiddleware']);
 *     }
 * }
 * ```
 */
trait ExcludingMiddlewares
{
    /**
     * Danh sách middleware bị loại trừ.
     *
     * @var ?string[]
     */
    protected ?array $excludedMiddlewares = null;

    /**
     * Loại bỏ một hoặc nhiều middleware khỏi quá trình xử lý.
     *
     * @param string|array $middleware Tên hoặc danh sách middleware cần loại bỏ.
     * @return self Trả về chính đối tượng để hỗ trợ method chaining.
     *
     * @description
     * Phương thức này cho phép loại bỏ middleware bằng cách lưu danh sách middleware bị loại trừ.
     * Hệ thống middleware có thể kiểm tra danh sách này để quyết định bỏ qua middleware tương ứng.
     *
     * @example
     * ```php
     * $controller->withoutMiddleware('AuthMiddleware');
     * $controller->withoutMiddleware(['CsrfMiddleware', 'RateLimitMiddleware']);
     * ```
     */
    public function withoutMiddleware(string|array $middleware): self
    {
        $this->excludedMiddlewares = Arrays::asArray($middleware);
        return $this;
    }
}
