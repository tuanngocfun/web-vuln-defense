<?php
namespace App\Core\Http\Cookie;

/**
 * Interface CookieQueue
 *
 * @namespace App\Core\Http\Cookie
 * @description
 * Interface này định nghĩa các phương thức để quản lý hàng đợi cookie.
 * Các cookie có thể được xếp vào hàng đợi để gửi hoặc xóa trong phản hồi HTTP tiếp theo.
 *
 * @example
 * ```php
 * use App\Core\Http\Cookie\CookieQueue;
 * use App\Core\Http\Cookie\CookieOptions;
 *
 * $queue = new MyCookieQueue();
 * $options = new CookieOptions();
 * $options->path = '/';
 * $options->secure = true;
 *
 * // Xếp hàng cookie để gửi
 * $queue->enqueueSend('session', 'abc123', 3600, $options);
 *
 * // Xếp hàng cookie để xóa
 * $queue->enqueueDestroy('session', $options);
 *
 * // Gửi tất cả cookie
 * $queue->dispatch();
 * ```
 */
interface CookieQueue
{
    function enqueueSend(string $name, string $value, int $seconds, ?CookieOptions $options = null): void;
    function enqueueDestroy(string $name, ?CookieOptions $options = null): void;
    function dispatch(): void;
}
