<?php
namespace App\Core\Http\Cookie;

/**
 * Lớp PhpCookieQueue quản lý hàng đợi cookie và thực thi chúng trong phản hồi HTTP.
 *
 * @namespace App\Core\Http\Cookie
 * @description
 * Lớp này triển khai giao diện `CookieQueue`, cung cấp cơ chế để:
 * - **Xếp hàng (enqueue) cookie** để gửi trong phản hồi HTTP tiếp theo.
 * - **Xóa cookie** bằng cách đặt thời gian hết hạn về quá khứ.
 * - **Thực thi (dispatch) tất cả cookie** trong hàng đợi.
 *
 * Cookie được xử lý bằng `setcookie()` của PHP, và có thể được tùy chỉnh với `CookieOptions`.
 *
 * @example
 * ```php
 * use App\Core\Http\Cookie\PhpCookieQueue;
 * use App\Core\Http\Cookie\CookieOptions;
 * use App\Core\Http\Cookie\CookieWriter;
 *
 * $queue = new PhpCookieQueue(new MyCookieWriter());
 *
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
class PhpCookieQueue implements CookieQueue
{
    /**
     * Hàng đợi chứa các hành động thiết lập cookie.
     *
     * @var array<\Closure():mixed>
     */
    private array $queue = [];

    /**
     * @param CookieWriter $cookieWriter Đối tượng để xử lý giá trị cookie trước khi lưu trữ.
     */
    public function __construct(private readonly CookieWriter $cookieWriter) {
        
    }
    
    /**
     * Thêm một cookie vào hàng đợi để gửi trong phản hồi HTTP tiếp theo.
     *
     * @param string $name Tên cookie.
     * @param string $value Giá trị cookie.
     * @param int $seconds Thời gian tồn tại của cookie (tính bằng giây).
     * @param ?CookieOptions $options (Tùy chọn) Cấu hình cookie.
     * @return void
     *
     * @description
     * Nếu `$seconds` < 0, cookie sẽ bị xóa bằng cách gọi `enqueueDestroy()`.
     * Nếu `$seconds === 0`, cookie sẽ được thiết lập với thời gian hết hạn `0` (session cookie).
     *
     * @example
     * ```php
     * $queue->enqueueSend('session', 'abc123', 3600, $options);
     * ```
     */
    #[\Override]
    public function enqueueSend(
        string $name,
        string $value,
        int $seconds,
        ?CookieOptions $options = null
    ): void {
        if ($seconds < 0) {
            $this->enqueueDestroy($name, $options);
            return;
        }
        $timeCallback = fn() => $seconds !== 0 ? time() + $seconds : 0;
        $value = $this->cookieWriter->write(trim($value));
        $command = fn() => setcookie($name, $value, static::makeOptions($timeCallback(), $options));
        $this->queue[] = $command;
    }

    /**
     * Thêm một cookie vào hàng đợi để xóa trong phản hồi HTTP tiếp theo.
     *
     * @param string $name Tên cookie cần xóa.
     * @param ?CookieOptions $options (Tùy chọn) Cấu hình cookie.
     * @return void
     *
     * @description
     * Cookie sẽ được xóa bằng cách đặt thời gian hết hạn về quá khứ (`time() - 1`).
     *
     * @example
     * ```php
     * $queue->enqueueDestroy('session', $options);
     * ```
     */
    #[\Override]
    public function enqueueDestroy(string $name, ?CookieOptions $options = null): void {
        $command = fn() => setcookie($name, '', static::makeOptions(time() - 1, $options));
        $this->queue[] = $command;
    }

    /**
     * Tạo mảng cấu hình cho `setcookie()`.
     *
     * @param int $expires Thời gian hết hạn của cookie.
     * @param ?CookieOptions $options Cấu hình cookie.
     * @return array Mảng cấu hình để sử dụng với `setcookie()`.
     *
     * @description
     * Chuyển đổi `CookieOptions` thành mảng phù hợp với `setcookie()`, đồng thời thêm thuộc tính `expires`.
     *
     * @example
     * ```php
     * $options = PhpCookieQueue::makeOptions(time() + 3600, new CookieOptions());
     * ```
     */
    private static function makeOptions(int $expires, ?CookieOptions $options) {
        $options = $options?->toArray() ?? [];
        return [...$options, 'expires' => $expires];
    }

    /**
     * Thực thi tất cả cookie trong hàng đợi.
     *
     * @return void
     *
     * @description
     * Gọi `setcookie()` cho tất cả cookie đã được xếp hàng và xóa hàng đợi sau khi thực thi.
     *
     * @example
     * ```php
     * $queue->dispatch();
     * ```
     */
    #[\Override]
    public function dispatch(): void {
        foreach ($this->queue as $command) {
            call_user_func($command);
        }
        $this->queue = [];
    }
}
