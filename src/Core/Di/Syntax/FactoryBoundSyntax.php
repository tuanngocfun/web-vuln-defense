<?php
namespace App\Core\Di\Syntax;

use App\Core\Di\Contracts\BindingInSyntax;
use App\Core\Di\Contracts\FactoryBindingContext;

/**
 * Lớp FactoryBoundSyntax ánh xạ một ID với một factory (hàm tạo đối tượng) trong DI Container.
 *
 * @namespace App\Core\Di\Syntax
 * @description
 * Đây là một phần của hệ thống Dependency Injection (DI) Container, cho phép ánh xạ một ID với
 * một factory (closure hoặc callable) để tạo đối tượng. Cách tiếp cận này đặc biệt hữu ích trong
 * các trường hợp cần logic khởi tạo tuỳ chỉnh hoặc phức tạp.
 *
 * Sau khi ánh xạ, bạn có thể định nghĩa phạm vi của đối tượng:
 * - **Transient Scope**: Mỗi lần yêu cầu sẽ tạo ra một phiên bản mới.
 * - **Singleton Scope**: Dùng lại cùng một phiên bản cho tất cả các yêu cầu.
 *
 * @example
 * ```php
 * $context = new MyFactoryBindingContext();
 * $binding = new FactoryBoundSyntax($context, 'my.service', fn($ctx) => new MyService($ctx->get('dependency')));
 *
 * // Định nghĩa phạm vi
 * $binding->inTransientScope(); // Tạo phiên bản mới mỗi lần.
 * $binding->inSingletonScope(); // Dùng lại cùng một phiên bản.
 * ```
 */
class FactoryBoundSyntax implements BindingInSyntax
{
        /** @var FactoryBindingContext $context Bối cảnh của ánh xạ factory trong DI Container. */
    private FactoryBindingContext $context;
    /** @var string $id Tên định danh của đối tượng được ánh xạ. */
    private string $id;
    /** @var string $id Tên định danh của đối tượng được ánh xạ. */
    private \Closure $factory;

    /**
     * Tạo một đối tượng FactoryBoundSyntax mới.
     *
     * @param FactoryBindingContext $context Bối cảnh gắn kết factory trong DI Container.
     * @param string $id Tên định danh của đối tượng cần gắn kết.
     * @param callable $factory Hàm hoặc closure để tạo đối tượng.
     *
     * @description
     * Phương thức khởi tạo nhận vào bối cảnh `FactoryBindingContext`, tên định danh ID,
     * và một factory function. Mặc định phạm vi là **Transient Scope**.
     *
     * @example
     * ```php
     * $binding = new FactoryBoundSyntax($context, 'my.service', fn($ctx) => new MyService($ctx->get('dependency')));
     * ```
     */
    public function __construct(FactoryBindingContext $context, string $id, callable $factory)
    {
        $this->context = $context;
        $this->id = $id;
        $this->factory = \Closure::fromCallable($factory);
        $this->inTransientScope();// Mặc định sử dụng Transient Scope
    }

    /**
     * Đặt phạm vi là Transient Scope.
     *
     * @description
     * Trong Transient Scope, mỗi lần yêu cầu sẽ tạo ra một phiên bản mới của đối tượng.
     * Hàm này cũng đảm bảo trạng thái singleton bị xóa nếu đã được đặt trước đó.
     *
     * @return void
     *
     * @example
     * ```php
     * $binding->inTransientScope();
     * ```
     */
    #[\Override]
    #[\Override]
    public function inTransientScope(): void {
        $this->context->bindToFactory($this->id, $this->factory);
        $this->context->removeSingletonScoped($this->id);
    }

    /**
     * Đặt phạm vi là Singleton Scope.
     *
     * @description
     * Trong Singleton Scope, cùng một phiên bản của đối tượng sẽ được sử dụng lại
     * cho tất cả các yêu cầu. Hàm này đảm bảo trạng thái singleton được thiết lập.
     *
     * @return void
     *
     * @example
     * ```php
     * $binding->inSingletonScope();
     * ```
     */
    #[\Override]
    public function inSingletonScope(): void {
        $this->context->bindToFactory($this->id, $this->factory);
        $this->context->addSingletonScoped($this->id);
    }
}
