<?php
namespace App\Core\Http\Guard;

/**
 * Interface HasGuard
 *
 * @namespace App\Core\Http\Guard
 * @description
 * Interface này định nghĩa các phương thức liên quan đến bảo vệ quyền truy cập (guard) trong hệ thống.
 * Các lớp triển khai `HasGuard` có thể liên kết với một guard để xác thực và phân quyền.
 *
 * @example
 * ```php
 * class UserController implements HasGuard {
 *     private ?object $guard = null;
 *     
 *     public function getPossibleGuards(): array {
 *         return ['App\\Http\\Guards\\UserGuard'];
 *     }
 *     
 *     public function setGuard(object $guard): void {
 *         $this->guard = $guard;
 *     }
 * }
 * 
 * // Sử dụng trong hệ thống
 * $controller = new UserController();
 * $guardClass = $controller->getPossibleGuards()[0];
 * $controller->setGuard(new $guardClass());
 * ```
 */
interface HasGuard
{
    /**
     * Lấy danh sách các guard có thể sử dụng.
     *
     * @return string[] Mảng chứa các tên đầy đủ (fully qualified names) của các lớp guard có thể áp dụng.
     *
     * @description
     * Phương thức này trả về danh sách các guard có thể sử dụng để bảo vệ đối tượng.
     * Các guard có thể được ánh xạ dựa trên namespace và tên class hiện tại.
     *
     * @example
     * ```php
     * $guards = $controller->getPossibleGuards();
     * ```
     */
    function getPossibleGuards(): array;

    /**
     * Gán một guard cho đối tượng.
     *
     * @param object $guard Đối tượng guard để gán.
     * @return void
     *
     * @description
     * Phương thức này cho phép thiết lập một guard để kiểm tra quyền truy cập và bảo vệ tài nguyên.
     *
     * @example
     * ```php
     * $controller->setGuard(new UserGuard());
     * ```
     */
    function setGuard(object $guard): void;
}
