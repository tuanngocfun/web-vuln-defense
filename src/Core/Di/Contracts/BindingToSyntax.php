<?php
namespace App\Core\Di\Contracts;

/**
 * Interface BindingToSyntax
 * 
 * @description
 * Đây là một phần quan trọng của hệ thống Dependency Injection (DI) Container. Interface này định nghĩa
 * các phương thức để ánh xạ một ID với các kiểu phụ thuộc khác nhau:
 * - Một class.
 * - Chính nó (self).
 * - Một factory (closure hoặc callable).
 * - Một giá trị cố định.
 * 
 * Các phương thức này trả về một đối tượng cho phép tiếp tục cấu hình phạm vi (scope) như 
 * `inTransientScope` hoặc `inSingletonScope`.
 *
 * @example
 * ```php
 * $container = new ServiceContainer();
 * 
 * // Ánh xạ ID với một class
 * $container->bind('my.service')->to(MyService::class)->inSingletonScope();
 * 
 * // Ánh xạ ID với chính nó
 * $container->bind('my.service')->toSelf()->inTransientScope();
 * 
 * // Ánh xạ ID với một factory
 * $container->bind('my.service')->toFactory(fn($ctx) => new MyService($ctx->get('dependency')))->inSingletonScope();
 * 
 * // Ánh xạ ID với một giá trị cố định
 * $container->bind('app.name')->toConstant('MyApplication');
 * ```
 */
interface BindingToSyntax
{
    /**
     * Ánh xạ ID với một class cụ thể.
     *
     * @param string $class Tên class để ánh xạ.
     * @return BindingInSyntax Đối tượng cho phép tiếp tục cấu hình phạm vi.
     *
     * @description
     * Dùng khi bạn muốn ID ánh xạ đến một class cụ thể trong DI Container.
     * Phương thức này thường được sử dụng khi cần khởi tạo đối tượng tự động 
     * hoặc áp dụng autowiring.
     *
     * @example
     * ```php
     * $container->bind('my.service')->to(MyService::class)->inSingletonScope();
     * ```
     */
    function to(string $class): BindingInSyntax;
    /**
     * Ánh xạ ID với chính nó (self).
     *
     * @return BindingInSyntax Đối tượng cho phép tiếp tục cấu hình phạm vi.
     *
     * @description
     * Dùng khi ID và tên class giống nhau. Điều này giúp rút ngắn cú pháp ánh xạ
     * và giảm thiểu lỗi khi thiết lập DI Container.
     *
     * @example
     * ```php
     * // Gắn kết 'MyService' với chính nó (ID = tên class)
     * $container->bind('MyService')->toSelf()->inSingletonScope();
     * ```
     */
    function toSelf(): BindingInSyntax;
    /**
     * Ánh xạ ID với một factory function.
     *
     * @param callable $factory Hàm hoặc closure để tạo đối tượng.
     * @return BindingInSyntax Đối tượng cho phép tiếp tục cấu hình phạm vi.
     *
     * @description
     * Dùng khi đối tượng cần khởi tạo dựa trên logic runtime hoặc phụ thuộc phức tạp.
     * Factory function nhận vào một InjectionContext để lấy các phụ thuộc khác.
     *
     * @example
     * ```php
     * $container->bind('my.service')->toFactory(function($ctx) {
     *     $dependency =( $ctx->get'my.dependency');
     *     return new MyService($dependency);
     * })->inTransientScope();
     * ```
     */
    function toFactory(callable $factory): BindingInSyntax;
    /**
     * Ánh xạ ID với một giá trị cố định.
     *
     * @param mixed $constant Giá trị cố định để gắn kết.
     * @return BindingNoopSyntax Đối tượng không yêu cầu định nghĩa phạm vi.
     *
     * @description
     * Dùng để lưu trữ các thông tin cấu hình hoặc hằng số, chẳng hạn như:
     * - Đường dẫn cơ sở dữ liệu.
     * - Các khóa API.
     * - Tên ứng dụng hoặc ngôn ngữ mặc định.
     *
     * Không cần định nghĩa phạm vi cho giá trị cố định, vì nó không thể thay đổi.
     *
     * @example
     * ```php
     * $container->bind('app.name')->toConstant('MyApplication');
     * ```
     */
    function toConstant(mixed $constant): BindingNoopSyntax;
}
