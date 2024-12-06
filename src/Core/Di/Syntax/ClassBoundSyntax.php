<?php
namespace App\Core\Di\Syntax;

use App\Core\Di\Contracts\BindingInSyntax;
use App\Core\Di\Contracts\ClassBindingContext;

class ClassBoundSyntax implements BindingInSyntax
{
    private ClassBindingContext $context;
    private string $id;
    private string $class;

    public function __construct(ClassBindingContext $context, string $id, string $class)
    {
        $this->context = $context;
        $this->id = $id;
        $this->class = $class;
        $this->inTransientScope();
    }

    /* Transient scope phù hợp khi bạn muốn mỗi yêu cầu hoặc tiến trình hoạt động trên một đối tượng hoàn toàn độc lập
     (stateless hoặc tránh chia sẻ trạng thái). Ví dụ:

    + Các lớp xử lý logic tạm thời hoặc không cần lưu trạng thái lâu dài.
    + Các request handler, validator, hoặc worker.
    */
    #[\Override]
    public function inTransientScope(): void {
        $this->context->bindToClass($this->id, $this->class);
        $this->context->removeSingletonScoped($this->id);
    }

    /* Singleton scope phù hợp cho các đối tượng cần được chia sẻ trạng thái hoặc sử dụng tài nguyên chung. 
    Một số trường hợp sử dụng:

    +) Kết nối cơ sở dữ liệu:
    Chỉ cần một connection pool được chia sẻ bởi toàn bộ ứng dụng.
    +) Cấu hình ứng dụng:
    Các thông tin cấu hình (config) nên được giữ lại để truy cập nhanh hơn.
    +) Caching Service:
    Lớp quản lý bộ nhớ đệm để tối ưu hóa hiệu suất.
    +) Logger:
    Một trình ghi log dùng chung, giúp đồng bộ hóa việc ghi log từ nhiều phần của ứng dụng.
    */
    #[\Override]
    public function inSingletonScope(): void {
        $this->context->bindToClass($this->id, $this->class);
        $this->context->addSingletonScoped($this->id);
    }
}
