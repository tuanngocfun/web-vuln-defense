<?php
namespace App\Core\Http\Middleware\Traits;

/**
 * Trait ManagesMiddlewares
 *
 * @namespace App\Core\Http\Middleware\Traits
 * @description
 * Trait này kết hợp cả `HasMiddlewares` và `ExcludingMiddlewares` để quản lý danh sách middleware áp dụng cho một đối tượng.
 * Nó cho phép:
 * - Đăng ký middleware cần sử dụng (`HasMiddlewares`).
 * - Loại bỏ middleware không muốn sử dụng (`ExcludingMiddlewares`).
 *
 * @example
 * ```php
 * use App\Core\Http\Middleware\Traits\ManagesMiddlewares;
 *
 * class MyController {
 *     use ManagesMiddlewares;
 *
 *     public function __construct() {
 *         $this->middleware(['AuthMiddleware', 'LoggingMiddleware']);
 *         $this->withoutMiddleware('CsrfMiddleware');
 *     }
 * }
 * ```
 */
trait ManagesMiddlewares
{
    use HasMiddlewares;
    use ExcludingMiddlewares;
}
