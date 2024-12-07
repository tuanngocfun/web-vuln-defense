<?php
namespace App\Core\Di\Syntax;

use App\Core\Di\Contracts\BindingInSyntax;
use App\Core\Di\Contracts\BindingNoopSyntax;
use App\Core\Di\Contracts\BindingToSyntax;
use App\Core\Di\Contracts\DiBindingContext;

/**
 * Lớp DefaultBindingToSyntax định nghĩa cú pháp để ánh xạ ID với các loại phụ thuộc khác nhau trong DI Container.
 *
 * @namespace App\Core\Di\Syntax
 * @description
 * Đây là một phần của hệ thống Dependency Injection (DI) Container, cung cấp các phương thức ánh xạ (binding) 
 * để gắn kết một ID với:
 * - Một class để tự động khởi tạo (autowired).
 * - Chính nó (self), phục vụ trường hợp đối tượng singleton.
 * - Một factory (closure) để tạo đối tượng tuỳ chỉnh.
 * - Một giá trị hằng số cố định (constant).
 *
 * Sử dụng lớp này, bạn có thể cấu hình các gắn kết linh hoạt trong DI Container, hỗ trợ khả năng mở rộng 
 * và quản lý phụ thuộc tốt hơn.
 *
 * @example
 * ```php
 * $container = new MyDiContainer();
 * $binding = $container->bind('my.service');
 * 
 * // Gắn kết với class
 * $binding->to(MyService::class)->inSingletonScope();
 * 
 * // Gắn kết với chính nó
 * $binding->toSelf()->inTransientScope();
 * 
 * // Gắn kết với một factory
 * $binding->toFactory(fn($ctx) => new MyService($ctx->get('dependency')))->inSingletonScope();
 * 
 * // Gắn kết với một giá trị hằng số
 * $binding->toConstant('fixed value');
 * ```
 */
class DefaultBindingToSyntax implements BindingToSyntax
{
    private DiBindingContext $context;
    private string $id;

    /**
     * Tạo một đối tượng DefaultBindingToSyntax mới.
     *
     * @param DiBindingContext $context Bối cảnh gắn kết trong DI Container.
     * @param string $id Tên định danh của phụ thuộc cần gắn kết.
     */
    public function __construct(DiBindingContext $context, string $id)
    {
        $this->context = $context;
        $this->id = $id;
    }

    /**
     * Ánh xạ ID với một class.
     *
     * @param string $class Tên class để ánh xạ.
     * @return BindingInSyntax Đối tượng cho phép tiếp tục định nghĩa phạm vi (scope).
     * @description Dùng khi cần ánh xạ một ID với một class cụ thể để khởi tạo tự động.
     */
    #[\Override]
    public function to(string $class): BindingInSyntax {
        return new ClassBoundSyntax($this->context, $this->id, $class);
    }

    /**
     * Ánh xạ ID với chính nó.
     *
     * @return BindingInSyntax Đối tượng cho phép tiếp tục định nghĩa phạm vi (scope).
     * @description Thích hợp khi ID và class có cùng tên hoặc trong các trường hợp singleton đơn giản.
     */
    #[\Override]
    public function toSelf(): BindingInSyntax {
        return new SelfBoundSyntax($this->context, $this->id);
    }

    /**
     * Ánh xạ ID với một factory (closure).
     *
     * @param callable $factory Hàm hoặc closure để tạo đối tượng.
     * @return BindingInSyntax Đối tượng cho phép tiếp tục định nghĩa phạm vi (scope).
     * @description Dùng khi đối tượng cần khởi tạo phụ thuộc vào logic runtime hoặc các phụ thuộc khác.
     */
    #[\Override]
    public function toFactory(callable $factory): BindingInSyntax {
        return new FactoryBoundSyntax($this->context, $this->id, $factory);
    }

    /**
     * Ánh xạ ID với một giá trị hằng số.
     *
     * @param mixed $constant Giá trị cố định để gắn kết.
     * @return BindingNoopSyntax Đối tượng không yêu cầu thêm định nghĩa phạm vi.
     * @description Dùng để lưu trữ các giá trị cố định như cấu hình, đường dẫn cơ sở dữ liệu, hoặc khóa API.
     */
    #[\Override]
    public function toConstant(mixed $constant): BindingNoopSyntax {
        return new ConstantBoundSyntax($this->context, $this->id, $constant);
    }
}
