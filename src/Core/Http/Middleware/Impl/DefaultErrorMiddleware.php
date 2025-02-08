<?php
namespace App\Core\Http\Middleware\Impl;

use App\Core\Exceptions\HttpException;
use App\Core\Http\Middleware\ErrorMiddleware;
use App\Core\Http\Request\Request;
use App\Core\Http\Response\Response;
use Closure;
use Throwable;
/**
 * Lớp DefaultErrorMiddleware xử lý ngoại lệ trong pipeline middleware.
 *
 * @namespace App\Core\Http\Middleware\Impl
 * @description
 * Lớp này triển khai giao diện `ErrorMiddleware`, giúp quản lý lỗi trong hệ thống HTTP middleware.
 * Khi một lỗi xảy ra, lớp này sẽ kiểm tra xem lỗi có phải là `HttpException` không.
 * Nếu có, nó sẽ trả về một phản hồi HTTP với mã lỗi và thông điệp tương ứng.
 * Nếu lỗi không phải là `HttpException`, nó sẽ ném lỗi tiếp tục để trình xử lý khác xử lý.
 *
 * @example
 * ```php
 * use App\Core\Http\Middleware\Impl\DefaultErrorMiddleware;
 * use App\Core\Http\Request\Request;
 * use App\Core\Http\Response\Response;
 * use App\Core\Exceptions\HttpException;
 * use Throwable;
 * use Closure;
 *
 * $middleware = new DefaultErrorMiddleware();
 * 
 * $response = $middleware->handle(
 *     new HttpException(404, "Not Found"), 
 *     new Request(), 
 *     function() { return new Response(); }
 * );
 *
 * echo $response->getStatusCode(); // 404
 * ```
 */
class DefaultErrorMiddleware implements ErrorMiddleware
{
    /**
     * Xử lý lỗi trong hệ thống middleware.
     *
     * @param Throwable $e Ngoại lệ cần xử lý.
     * @param Request $request Đối tượng yêu cầu HTTP.
     * @param Closure $next Middleware tiếp theo trong chuỗi xử lý.
     * @return Response Phản hồi HTTP.
     * @throws Throwable Nếu lỗi không phải là `HttpException`, ném lỗi tiếp tục để xử lý bởi hệ thống cấp cao hơn.
     *
     * @description
     * Phương thức này kiểm tra nếu lỗi là một `HttpException`, nó sẽ tạo phản hồi HTTP với mã lỗi tương ứng.
     * Nếu không, lỗi sẽ được ném lại để tiếp tục xử lý bởi hệ thống middleware hoặc trình xử lý lỗi cấp cao hơn.
     *
     * @example
     * ```php
     * try {
     *     $response = $middleware->handle(new HttpException(403, "Forbidden"), new Request(), fn() => new Response());
     *     echo $response->getStatusCode(); // 403
     * } catch (Throwable $e) {
     *     echo "Unhandled error: " . $e->getMessage();
     * }
     * ```
     */
    #[\Override]
    public function handle(Throwable $e, Request $request, Closure $next): Response {
        if ($e instanceof HttpException) {
            return response()->err($e->getStatusCode(), $e->getMessage());
        }
        throw $e;
    }
}
