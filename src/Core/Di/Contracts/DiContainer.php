<?php
namespace App\Core\Di\Contracts;

/**
 * Interface DiContainer
 *
 * @description
 * Định nghĩa các phương thức cho DI Container để quản lý các gắn kết (bindings) và lấy đối tượng (resolve).
 *
 * @example
 * ```php
 * $container->bind('my.service')->to(MyService::class)->inSingletonScope();
 * $service = $container->get('my.service');
 * ```
 */
interface DiContainer extends ReadonlyDiContainer
{
    function bind(string $id): BindingToSyntax;
    function bindIf(string $id): BindingToSyntax;
    function unbind(string $id): self;
}
