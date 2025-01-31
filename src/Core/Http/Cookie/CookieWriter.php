<?php
namespace App\Core\Http\Cookie;

/**
 * Interface CookieWriter
 *
 * @namespace App\Core\Http\Cookie
 * @description
 * Interface này định nghĩa phương thức để ghi giá trị cookie.
 * CookieWriter có thể được sử dụng để mã hóa, ký, hoặc xử lý dữ liệu cookie trước khi lưu trữ.
 *
 * @example
 * ```php
 * class MyCookieWriter implements CookieWriter {
 *     public function write(string $value): string {
 *         return base64_encode($value);
 *     }
 * }
 *
 * $writer = new MyCookieWriter();
 * $cookieValue = $writer->write('my-data');
 * setcookie('my_cookie', $cookieValue, time() + 3600, '/');
 * ```
 */
interface CookieWriter
{
    function write(string $value): string;
}
