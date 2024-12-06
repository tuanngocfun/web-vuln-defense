<?php
namespace App\Core\Di\Syntax;

use App\Core\Di\Contracts\BindingNoopSyntax;
use App\Core\Di\Contracts\ConstantBindingContext;

/**
 * Lớp ConstantBoundSyntax ánh xạ một giá trị hằng số vào DI Container.
 *
 * @namespace App\Core\Di\Syntax
 * @description
 * Khi xây dựng một ứng dụng lớn, bạn có thể cần lưu các giá trị cố định như:
 * - Đường dẫn cơ sở dữ liệu.
 * - Các khóa API.
 * - Ngôn ngữ mặc định của ứng dụng.
 *
 * Thay vì "cứng mã" (hardcode), bạn có thể lưu trữ chúng trong DI Container để tái sử dụng.
 *
 * @example
 * Ví dụ sử dụng:
 * ```php
 * $context = new MyConstantBindingContext();
 * new ConstantBoundSyntax($context, 'database.connection', 'mysql:host=localhost;dbname=test');
 *
 * // DI Container sẽ lưu:
 * // Tên: database.connection
 * // Giá trị: mysql:host=localhost;dbname=test
 * ```
 */
class ConstantBoundSyntax implements BindingNoopSyntax
{
    private ConstantBindingContext $context;
    private string $id;
    private mixed $constant;

    /**
     * Tạo một đối tượng ConstantBoundSyntax mới.
     *
     * @param ConstantBindingContext $context Bối cảnh gắn kết hằng số.
     * @param string $id Tên định danh của hằng số.
     * @param mixed $constant Giá trị của hằng số.
     */
    public function __construct(ConstantBindingContext $context, string $id, mixed $constant)
    {
        $this->context = $context;
        $this->id = $id;
        $this->constant = $constant;
        $this->bind();
    }

    /**
     * Ánh xạ giá trị vào DI Container.
     */
    private function bind() {
        $this->context->bindToConstant($this->id, $this->constant);
    }
}
