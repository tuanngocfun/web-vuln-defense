<?php
namespace App\Core\Di\Contracts;

/**
 * Interface BindingNoopSyntax
 *
 * @description
 * Một interface rỗng dùng cho các ánh xạ không cần định nghĩa phạm vi.
 * Ví dụ: Khi ánh xạ một giá trị cố định, không cần phải định nghĩa Transient hay Singleton Scope.
 *
 * @example
 * ```php
 * $binding = $container->bind('app.name')->toConstant('MyApplication');
 * // Không cần gọi inTransientScope() hay inSingletonScope().
 * ```
 */
interface BindingNoopSyntax {}
