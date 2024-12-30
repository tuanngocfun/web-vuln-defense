<?php
namespace App\Core\Global;

use App\Core\Http\Request\RequestGlobalCollection;


/**
 * Interface GlobalCollection
 *
 * @namespace App\Core\Global
 * @description
 * Interface này mở rộng từ `RequestGlobalCollection` và định nghĩa các phương thức
 * cần thiết để truy cập dữ liệu từ các biến siêu toàn cục trong PHP như `$_GET` và `$_POST`.
 *
 * Mục đích của interface là cung cấp một giao diện thống nhất để truy cập các tham số
 * yêu cầu HTTP từ client, giúp việc xử lý dữ liệu trong ứng dụng dễ dàng và linh hoạt hơn.
 *
 * @example
 * ```php
 * class MyGlobalCollection implements GlobalCollection {
 *     public function get(): array {
 *         return $_GET;
 *     }
 * 
 *     public function post(): array {
 *         return $_POST;
 *     }
 * 
 *     // Các phương thức khác từ RequestGlobalCollection...
 * }
 *
 * $collection = new MyGlobalCollection();
 * $getParams = $collection->get();
 * $postData = $collection->post();
 * ```
 */
interface GlobalCollection extends RequestGlobalCollection
{
    function get(): array;
    function post(): array;
}
