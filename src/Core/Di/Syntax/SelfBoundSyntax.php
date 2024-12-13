<?php
namespace App\Core\Di\Syntax;

use App\Core\Di\Contracts\ClassBindingContext;

/**
 * Lớp SelfBoundSyntax ánh xạ một ID với chính nó trong DI Container.
 *
 * @namespace App\Core\Di\Syntax
 * @description
 * Lớp này là một phần của hệ thống Dependency Injection (DI) Container. Nó được sử dụng khi
 * bạn muốn ánh xạ một ID với chính nó, tức là ID và tên class là giống nhau.
 *
 * Điều này giúp giảm thiểu sự dư thừa khi cấu hình ánh xạ trong DI Container,
 * đặc biệt trong các trường hợp ID và tên class trùng khớp.
 *
 * @example
 * ```php
 * $context = new MyClassBindingContext();
 * $binding = new SelfBoundSyntax($context, 'MyService');
 * 
 * // Tương đương với:
 * // $context->bindToClass('MyService', 'MyService');
 * ```
 */
class SelfBoundSyntax extends ClassBoundSyntax
{
    /**
     * Tạo một đối tượng SelfBoundSyntax mới.
     *
     * @param ClassBindingContext $context Bối cảnh gắn kết class trong DI Container.
     * @param string $id Tên định danh của đối tượng cần gắn kết (ID = tên class).
     *
     * @description
     * Constructor này tự động ánh xạ ID với chính nó bằng cách truyền `id` hai lần
     * cho lớp cha `ClassBoundSyntax`.
     *
     * @example
     * ```php
     * $binding = new SelfBoundSyntax($context, 'MyService');
     * ```
     */
    public function __construct(ClassBindingContext $context, string $id)
    {
        parent::__construct($context, $id, $id);
    }
    
}
