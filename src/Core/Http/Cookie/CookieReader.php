<?php
namespace App\Core\Http\Cookie;

/**
 * Interface CookieReader
 *
 * @namespace App\Core\Http\Cookie
 * @description
 * Interface này định nghĩa phương thức để đọc và xử lý giá trị cookie từ client.
 * CookieReader được sử dụng trong các hệ thống yêu cầu xử lý dữ liệu từ cookie như giải mã, xác thực, hoặc kiểm tra tính hợp lệ.
 *
 * @example
 * ```php
 * class MyCookieReader implements CookieReader {
 *     public function read(string $value): string|false {
 *         // Giải mã cookie và kiểm tra tính hợp lệ
 *         $decoded = base64_decode($value);
 *         if ($decoded === false || !is_string($decoded)) {
 *             return false; // Cookie không hợp lệ
 *         }
 *         return $decoded; // Trả về giá trị giải mã
 *     }
 * }
 * 
 * $reader = new MyCookieReader();
 * $cookieValue = $_COOKIE['session'] ?? '';
 * $result = $reader->read($cookieValue);
 * if ($result === false) {
 *     echo "Invalid cookie!";
 * } else {
 *     echo "Cookie value: $result";
 * }
 * ```
 */
interface CookieReader
{
    /**
     * Đọc và xử lý giá trị cookie.
     *
     * @param string $value Giá trị cookie từ client.
     * @return string|false Giá trị đã xử lý hoặc `false` nếu cookie không hợp lệ.
     *
     * @description
     * Phương thức này được sử dụng để đọc giá trị cookie từ client và thực hiện các tác vụ xử lý như giải mã,
     * kiểm tra chữ ký, hoặc xác thực dữ liệu.
     * Nếu cookie không hợp lệ hoặc không thể xử lý, trả về `false`.
     *
     * @example
     * ```php
     * $reader = new MyCookieReader();
     * $value = $reader->read($_COOKIE['session'] ?? '');
     * ```
     */
    function read(string $value): string|false;
}
