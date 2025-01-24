<?php
namespace App\Core\Http\Cookie;

use App\Utils\Crypto;

/**
 * Lớp CookieSigner quản lý việc ký và xác thực cookie.
 *
 * @namespace App\Core\Http\Cookie
 * @description
 * Lớp này kết hợp hai giao diện `CookieReader` và `CookieWriter` để cung cấp cơ chế:
 * - **Ký cookie:** Thêm chữ ký vào giá trị cookie để đảm bảo tính toàn vẹn.
 * - **Đọc cookie:** Xác thực chữ ký của cookie trước khi sử dụng.
 *
 * Cách hoạt động:
 * - Cookie được mã hóa theo định dạng Base64 URL và kèm chữ ký SHA256.
 * - Chữ ký đảm bảo rằng giá trị cookie không bị giả mạo.
 *
 * @example
 * ```php
 * $signer = new CookieSigner('my-secret-key');
 *
 * // Ghi cookie
 * $signedCookie = $signer->write('my-value');
 * echo $signedCookie; // Kết quả: "encodedValue.signature"
 *
 * // Đọc cookie
 * $value = $signer->read($signedCookie);
 * if ($value === false) {
 *     echo "Invalid or tampered cookie!";
 * } else {
 *     echo "Cookie value: $value";
 * }
 * ```
 */
class CookieSigner implements CookieReader, CookieWriter
{
    public function __construct(private readonly string $cookieSecret) {

    }

    /**
     * Ghi cookie với chữ ký.
     *
     * @param string $value Giá trị cookie cần ký.
     * @return string Cookie đã được ký (định dạng: `encodedValue.signature`).
     *
     * @description
     * Phương thức này mã hóa giá trị cookie theo Base64 URL và thêm chữ ký SHA256
     * dựa trên giá trị đã mã hóa và khóa bí mật.
     *
     * @example
     * ```php
     * $signedCookie = $signer->write('my-value');
     * echo $signedCookie; // Kết quả: "encodedValue.signature"
     * ```
     */
    #[\Override]
    public function write(string $value): string {
        $encodedValue = Crypto::base64UrlEncode($value);
        $signature = Crypto::generateSignature($encodedValue, $this->cookieSecret);
        return $encodedValue . '.' . $signature;
    }

    /**
     * Đọc và xác thực cookie.
     *
     * @param string $value Giá trị cookie cần đọc.
     * @return string|false Giá trị cookie gốc nếu hợp lệ, hoặc `false` nếu không hợp lệ.
     *
     * @description
     * Phương thức này phân tách giá trị cookie thành phần mã hóa và chữ ký.
     * Sau đó, chữ ký được kiểm tra tính hợp lệ bằng cách so sánh với chữ ký được tạo từ giá trị mã hóa.
     *
     * @example
     * ```php
     * $value = $signer->read($signedCookie);
     * if ($value === false) {
     *     echo "Invalid cookie!";
     * } else {
     *     echo "Cookie value: $value";
     * }
     * ```
     */
    #[\Override]
    public function read(string $value): string|false {
        $parts = static::extractParts($value);
        if (!$parts) {
            return false;
        }

        $encodedValue = $parts['encodedValue'];
        $signature = $parts['signature'];
        if (!Crypto::isValidSignature($signature, $encodedValue, $this->cookieSecret)) {
            return false;
        }

        return Crypto::base64UrlDecode($encodedValue);
    }

    /**
     * Phân tách giá trị cookie thành phần mã hóa và chữ ký.
     *
     * @param string $value Giá trị cookie.
     * @return array|false Mảng chứa các phần `encodedValue` và `signature`, hoặc `false` nếu không hợp lệ.
     *
     * @description
     * Phương thức sử dụng biểu thức chính quy để phân tách cookie thành hai phần:
     * - `encodedValue`: Giá trị cookie đã mã hóa.
     * - `signature`: Chữ ký SHA256.
     *
     * @example
     * ```php
     * $parts = CookieSigner::extractParts('encodedValue.signature');
     * print_r($parts); // Kết quả: ['encodedValue' => '...', 'signature' => '...']
     * ```
     */
    private static function extractParts(string $value) {
        $valuePattern = "/^(?<encodedValue>.+)\.(?<signature>.+)$/";
        if (!preg_match($valuePattern, $value, $matches)) {
            return false;
        }
        return $matches;
    }
}
