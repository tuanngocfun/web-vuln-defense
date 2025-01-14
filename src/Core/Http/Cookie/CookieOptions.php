<?php
namespace App\Core\Http\Cookie;

use App\Constants\SameSite;
use App\Support\OptionsBase;

/**
 * Lớp CookieOptions đại diện cho các tùy chọn cấu hình cookie.
 *
 * @namespace App\Core\Http\Cookie
 * @description
 * Lớp này mở rộng từ `OptionsBase` và cung cấp các thuộc tính để cấu hình cookie:
 * - `path`: Đường dẫn mà cookie sẽ được gửi.
 * - `domain`: Tên miền cookie sẽ áp dụng.
 * - `secure`: Chỉ cho phép cookie được gửi qua HTTPS.
 * - `httponly`: Chỉ cho phép cookie được truy cập qua giao thức HTTP (không có sẵn trong JavaScript).
 * - `samesite`: Cách cookie sẽ được xử lý trong các yêu cầu cross-site.
 *
 * Ngoài ra, lớp cung cấp cơ chế tùy chỉnh giá trị đại diện thông qua phương thức `propToRepresentativeValue`.
 *
 * @example
 * ```php
 * use App\Constants\SameSite;
 * use App\Core\Http\Cookie\CookieOptions;
 *
 * $options = new CookieOptions();
 * $options->path = '/';
 * $options->domain = 'example.com';
 * $options->secure = true;
 * $options->httponly = true;
 * $options->samesite = SameSite::LAX;
 *
 * // Chuyển đổi các thuộc tính thành giá trị đại diện
 * $representative = $options->toArray();
 * ```
 */
class CookieOptions extends OptionsBase
{
    public ?string $path = null;/** @var ?string $path Đường dẫn mà cookie sẽ áp dụng. */
    public ?string $domain = null;/** @var ?string $domain Tên miền mà cookie sẽ áp dụng. */
    public ?bool $secure = null;/** @var ?bool $secure Cookie chỉ được gửi qua HTTPS nếu `true`. */
    public ?bool $httponly = null;/** @var ?bool $httponly Cookie chỉ truy cập qua HTTP nếu `true`. */
    public ?SameSite $samesite = null;/** @var ?SameSite $samesite Chính sách SameSite của cookie. */

    /**
     * Chuyển đổi giá trị của thuộc tính thành giá trị đại diện để gửi trong phản hồi HTTP.
     *
     * @param string $propName Tên thuộc tính.
     * @param mixed $propValue Giá trị của thuộc tính.
     * @return mixed Giá trị đại diện để sử dụng trong cấu hình cookie.
     *
     * @description
     * Phương thức này tùy chỉnh cách giá trị của các thuộc tính được xử lý trước khi sử dụng.
     * Nếu giá trị là một đối tượng `SameSite`, nó sẽ được chuyển đổi bằng phương thức `formatToSend()`.
     *
     * @example
     * ```php
     * $value = $options->propToRepresentativeValue('samesite', SameSite::STRICT);
     * echo $value; // Kết quả: "Strict"
     * ```
     */
    #[\Override]
    protected function propToRepresentativeValue(string $propName, mixed $propValue) {
        if ($propValue instanceof SameSite) {
            return $propValue->formatToSend();
        }
        else {
            return $propValue;
        }
    }
}
