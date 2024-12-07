<?php
namespace App\Core\Di\Contracts;

/**
 * Interface BindingInSyntax
 * 
 * @description
 * Định nghĩa các phương thức để thiết lập phạm vi của phụ thuộc đã được ánh xạ (binding scope).
 * Phạm vi có thể là:
 * - **Transient Scope**: Luôn tạo một phiên bản mới.
 * - **Singleton Scope**: Dùng lại cùng một phiên bản.
 *
 * @example
 * ```php
 * $binding = $container->bind('my.service')->to(MyService::class);
 * $binding->inSingletonScope(); // Sử dụng cùng một phiên bản trên toàn hệ thống.
 * ```
 */
interface BindingInSyntax
{
    function inTransientScope(): void;
    function inSingletonScope(): void;
}
