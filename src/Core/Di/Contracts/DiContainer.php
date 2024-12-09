<?php
namespace App\Core\Di\Contracts;

/**
 * Interface DiContainer
 *
 * @description
 * Định nghĩa các phương thức cho Dependency Injection (DI) Container để quản lý các gắn kết (bindings)
 * và lấy đối tượng (resolve). DI Container giúp bạn khởi tạo, cấu hình, và quản lý vòng đời của các phụ thuộc
 * một cách tự động.
 *
 * Các phương thức trong interface này cho phép bạn:
 * - Gắn kết (bind) các ID với phụ thuộc như class, factory, hoặc giá trị cố định.
 * - Gỡ bỏ (unbind) các gắn kết đã tồn tại.
 * - Lấy đối tượng (resolve) từ ID.
 *
 * @example
 * ```php
 * $container = new MyDiContainer(); // Giả định bạn có một triển khai của DiContainer.
 * 
 * // Gắn kết ID với một class và định nghĩa phạm vi
 * $container->bind('my.service')->to(MyService::class)->inSingletonScope();
 * 
 * // Gắn kết ID với một factory
 * $container->bind('my.factory')->toFactory(function($ctx) {
 *     $dependency = $ctx->get('dependency');
 *     return new MyService($dependency);
 * })->inTransientScope();
 * 
 * // Gắn kết ID với một giá trị cố định
 * $container->bind('app.name')->toConstant('MyApplication');
 * 
 * // Lấy đối tượng từ container
 * $service = $container->get('my.service'); // Trả về đối tượng MyService (singleton).
 * 
 * // Xóa gắn kết
 * $container->unbind('my.service');
 * ```
 */
interface DiContainer extends ReadonlyDiContainer
{
    /**
     * Gắn kết một ID với các loại phụ thuộc khác nhau.
     *
     * @param string $id Tên định danh.
     * @return BindingToSyntax Cú pháp để cấu hình ánh xạ.
     *
     * @description
     * Phương thức này khởi tạo quá trình gắn kết một ID với một phụ thuộc. Phụ thuộc có thể là:
     * - Một class: $container->bind('my.service')->to(MyService::class);
     * - Một factory: $container->bind('my.service')->toFactory(fn() => new MyService());
     * - Một giá trị cố định: $container->bind('app.name')->toConstant('MyApplication');
     *
     * Sau khi gọi `bind`, bạn có thể sử dụng các phương thức trong `BindingToSyntax` để tiếp tục định nghĩa.
     *
     * @example
     * ```php
     * $container->bind('my.service')->to(MyService::class)->inSingletonScope();
     * ```
     */
    function bind(string $id): BindingToSyntax;

    /**
     * Gắn kết một ID nếu chưa được ánh xạ trước đó.
     *
     * @param string $id Tên định danh.
     * @return BindingToSyntax Cú pháp để cấu hình ánh xạ.
     *
     * @description
     * Gắn kết ID chỉ khi nó chưa tồn tại trong container. Phương thức này hữu ích khi bạn muốn bảo đảm
     * rằng một ID chỉ được gắn kết một lần duy nhất.
     *
     * @example
     * ```php
     * $container->bindIf('my.service')->to(MyService::class)->inSingletonScope();
     * // Nếu 'my.service' đã được gắn kết trước đó, phương thức này sẽ không làm gì.
     * ```
     */
    function bindIf(string $id): BindingToSyntax;

    /**
     * Xóa một gắn kết đã tồn tại.
     *
     * @param string $id Tên định danh.
     * @return self Trả về chính container để hỗ trợ chain calls.
     *
     * @description
     * Loại bỏ gắn kết của một ID trong container, bao gồm:
     * - Gắn kết với class.
     * - Gắn kết với factory.
     * - Gắn kết với giá trị cố định.
     *
     * Sau khi gọi `unbind`, ID không còn tồn tại trong container, và mọi yêu cầu resolve sẽ thất bại.
     *
     * @example
     * ```php
     * $container->unbind('my.service');
     * ```
     */
    function unbind(string $id): self;
}
