<?php
namespace App\Core\Di\Contracts;

/**
 * Interface ScopedContext
 *
 * @description
 * Cung cấp các phương thức để quản lý trạng thái singleton trong DI Container.
 * Trạng thái singleton quyết định rằng một ID sẽ luôn sử dụng cùng một phiên bản của đối tượng trong toàn bộ ứng dụng.
 *
 * @example
 * ```php
 * $context->addSingletonScoped('my.service'); // Đánh dấu 'my.service' là singleton.
 * $context->removeSingletonScoped('my.service'); // Xóa trạng thái singleton.
 * ```
 */
interface ScopedContext
{
    function addSingletonScoped(string $id): void;
    function removeSingletonScoped(string $id): void;
}

/**
 * Interface ConstantBindingContext
 *
 * @description
 * Định nghĩa phương thức để ánh xạ một ID với một giá trị cố định (constant) trong DI Container.
 * Giá trị cố định thường là các thông số cấu hình như:
 * - Đường dẫn cơ sở dữ liệu.
 * - Các khóa API.
 * - Tên ứng dụng hoặc ngôn ngữ mặc định.
 *
 * @example
 * ```php
 * $context->bindToConstant('app.name', 'MyApplication'); // Gắn kết giá trị 'MyApplication' với ID 'app.name'.
 * ```
 */
interface ConstantBindingContext
{
    function bindToConstant(string $id, mixed $constant): void;
}

/**
 * Interface FactoryBindingContext
 *
 * @description
 * Định nghĩa phương thức để ánh xạ một ID với một factory function (closure hoặc callable) trong DI Container.
 * Factory function cho phép tạo ra đối tượng dựa trên logic runtime hoặc phụ thuộc phức tạp.
 *
 * @example
 * ```php
 * $context->bindToFactory('my.service', fn($ctx) => new MyService($ctx->get('dependency')));
 * ```
 */
interface FactoryBindingContext extends ScopedContext
{
    function bindToFactory(string $id, \Closure $factory): void;
}

/**
 * Interface ClassBindingContext
 *
 * @description
 * Định nghĩa phương thức để ánh xạ một ID với một class cụ thể trong DI Container.
 * Khi ID được ánh xạ với một class, DI Container sẽ tự động khởi tạo đối tượng dựa trên class đó.
 *
 * @example
 * ```php
 * $context->bindToClass('my.service', MyService::class); // Gắn kết ID 'my.service' với class 'MyService'.
 * ```
 */
interface ClassBindingContext extends ScopedContext
{
    function bindToClass(string $id, string $class): void;
}
/**
 * Interface DiBindingContext
 *
 * @description
 * Tích hợp các phương thức từ ConstantBindingContext, FactoryBindingContext và ClassBindingContext.
 * Cung cấp khả năng ánh xạ một ID với:
 * - Một giá trị cố định.
 * - Một factory.
 * - Một class.
 *
 * DiBindingContext là một phần cốt lõi của hệ thống DI Container, cho phép quản lý ánh xạ toàn diện.
 *
 * @example
 * ```php
 * $context->bindToConstant('app.name', 'MyApplication'); // Giá trị cố định.
 * $context->bindToFactory('my.service', fn($ctx) => new MyService($ctx->get('dependency'))); // Factory.
 * $context->bindToClass('my.service', MyService::class); // Class.
 * ```
 */
interface DiBindingContext extends ConstantBindingContext, FactoryBindingContext, ClassBindingContext
{

}
