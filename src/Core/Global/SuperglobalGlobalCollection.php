<?php
namespace App\Core\Global;

use App\Constants\HttpMethod;

/**
 * Lớp SuperglobalGlobalCollection cung cấp giao diện thống nhất để truy cập các biến siêu toàn cục trong PHP.
 *
 * @namespace App\Core\Global
 * @description
 * Lớp này được thiết kế để truy xuất dữ liệu từ các biến siêu toàn cục PHP như `$_SERVER`, `$_GET`, `$_POST`,
 * `$_FILES`, `$_COOKIE`, và headers HTTP. Nó cung cấp cơ chế cache để tối ưu việc xử lý các yêu cầu HTTP.
 *
 * @example
 * ```php
 * $globalCollection = new SuperglobalGlobalCollection();
 *
 * // Lấy thông tin từ $_GET
 * $getParams = $globalCollection->get();
 *
 * // Lấy body từ một yêu cầu POST/JSON
 * $body = $globalCollection->body();
 *
 * // Lấy headers HTTP
 * $headers = $globalCollection->headers();
 * ```
 */
class SuperglobalGlobalCollection implements GlobalCollection
{
    // Cache Body
    private ?array $body;
    // Cache Headers
    private array $headers;

    /**
     * Constructor khởi tạo lớp và cache headers HTTP.
     *
     * @description
     * Constructor sử dụng `getallheaders()` để lấy danh sách headers từ yêu cầu HTTP.
     * Nếu không thể lấy headers, nó sẽ sử dụng mảng rỗng mặc định.
     */
    public function __construct() {
        $this->body = null;
        $headers = getallheaders();
        $this->headers = $headers !== false ? $headers : [];
    }

    #[\Override]
    public function server(): array {
        return $_SERVER;
    }

    #[\Override]
    public function get(): array {
        return $_GET;
    }

    #[\Override]
    public function post(): array {
        return $_POST;
    }

    /**
     * Lấy body từ yêu cầu HTTP.
     *
     * @return array Mảng dữ liệu body.
     *
     * @description
     * - Nếu body đã được cache, trả về giá trị từ cache.
     * - Nếu yêu cầu sử dụng `$_POST`, trả về dữ liệu từ `$_POST`.
     * - Nếu body là JSON (trừ GET), nó sẽ được giải mã từ `php://input`.
     *
     * @example
     * ```php
     * $body = $globalCollection->body();
     * ```
     */
    #[\Override]
    public function body(): array {
        if ($this->body !== null) {
            return $this->body;
        }

        if (!empty($_POST)) {
            $this->body = $_POST;
        }
        elseif ($_SERVER['REQUEST_METHOD'] !== HttpMethod::GET) {
            $this->body = json_decode(file_get_contents('php://input'), true);
        }

        $this->body ??= [];
        return $this->body;
    }

    #[\Override]
    public function files(): array {
        return $_FILES;
    }

    #[\Override]
    public function cookie(): array {
        return $_COOKIE;
    }

    #[\Override]
    public function headers(): array {
        return $this->headers;
    }
}
