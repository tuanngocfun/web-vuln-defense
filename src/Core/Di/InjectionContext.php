<?php
namespace App\Core\Di;

use App\Core\Di\Contracts\ReadonlyDiContainer;

/**
 * Lớp InjectionContext cung cấp thông tin về bối cảnh injection trong DI Container.
 *
 * @namespace App\Core\Di
 * @description
 * Lớp này đại diện cho bối cảnh hiện tại khi một đối tượng hoặc phụ thuộc được tạo bởi DI Container.
 * Nó cung cấp:
 * - **ID của phụ thuộc**: ID đang được khởi tạo.
 * - **Container**: DI Container (chỉ đọc) để truy xuất thêm các phụ thuộc khác nếu cần.
 *
 * @example
 * ```php
 * $context = new InjectionContext('my.service', $container);
 * 
 * // Lấy ID
 * echo $context->id(); // Kết quả: 'my.service'
 * 
 * // Lấy container
 * $readonlyContainer = $context->container();
 * $dependency = $readonlyContainer->get('my.dependency');
 * ```
 */
final class InjectionContext
{
    public function __construct(
        private string $id,
        private ReadonlyDiContainer $container
        ) {
    }

    public function id(): string {
        return $this->id;
    }

    public function container(): ReadonlyDiContainer {
        return $this->container;
    }
}
