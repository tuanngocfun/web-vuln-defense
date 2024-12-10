<?php
namespace App\Core\Di\Contracts;

/**
 * Interface ReadonlyDiContainer
 *
 * @description
 * Đây là một phần của hệ thống Dependency Injection (DI) Container, cung cấp các phương thức chỉ đọc (readonly)
 * để:
 * - Truy xuất đối tượng (resolve) dựa trên ID.
 * - Kiểm tra trạng thái của các gắn kết (bindings) trong container.
 *
 * Interface này hữu ích khi bạn chỉ cần lấy thông tin hoặc truy xuất đối tượng từ DI Container mà không cần
 * thay đổi cấu hình của nó.
 *
 * @example
 * ```php
 * $container = new MyDiContainer(); // Giả định là triển khai của ReadonlyDiContainer.
 * 
 * // Kiểm tra xem một ID có được gắn kết không
 * if ($container->isBound('my.service')) {
 *     $service = $container->get('my.service'); // Lấy đối tượng nếu ID đã được gắn kết.
 * }
 * 
 * // Kiểm tra trạng thái gắn kết của ID
 * if ($container->isSingletonScoped('my.service')) {
 *     echo "my.service là singleton";
 * }
 * ```
 */
interface ReadonlyDiContainer
{
    /**
     * Lấy đối tượng (resolve) dựa trên ID.
     *
     * @param string $id Tên định danh của đối tượng cần truy xuất.
     * @return mixed Đối tượng đã được ánh xạ trong container.
     *
     * @description
     * Phương thức này trả về đối tượng đã được gắn kết với ID trong DI Container.
     * Nếu ID chưa được gắn kết, một ngoại lệ có thể được ném ra.
     *
     * @example
     * ```php
     * $service = $container->get('my.service');
     * ```
     */
    function get(string $id): mixed;

    /**
     * Kiểm tra xem một ID có được gắn kết hay không.
     *
     * @param string $id Tên định danh.
     * @return bool `true` nếu ID đã được gắn kết, `false` nếu không.
     *
     * @example
     * ```php
     * if ($container->isBound('my.service')) {
     *     echo "my.service đã được gắn kết.";
     * }
     * ```
     */
    function isBound(string $id): bool;

    /**
     * Kiểm tra xem một ID có được gắn kết với một giá trị cố định hay không.
     *
     * @param string $id Tên định danh.
     * @return bool `true` nếu ID được gắn kết với giá trị cố định, `false` nếu không.
     *
     * @example
     * ```php
     * if ($container->isConstantBound('app.name')) {
     *     echo "app.name được gắn kết với một giá trị cố định.";
     * }
     * ```
     */
    function isConstantBound(string $id): bool;

    /**
     * Kiểm tra xem một ID có được gắn kết với một factory hay không.
     *
     * @param string $id Tên định danh.
     * @return bool `true` nếu ID được gắn kết với một factory, `false` nếu không.
     *
     * @example
     * ```php
     * if ($container->isFactoryBound('my.factory')) {
     *     echo "my.factory được gắn kết với một factory.";
     * }
     * ```
     */
    function isFactoryBound(string $id): bool;

    /**
     * Kiểm tra xem một ID có được gắn kết với một class hay không.
     *
     * @param string $id Tên định danh.
     * @return bool `true` nếu ID được gắn kết với một class, `false` nếu không.
     *
     * @example
     * ```php
     * if ($container->isClassBound('my.service')) {
     *     echo "my.service được gắn kết với một class.";
     * }
     * ```
     */
    function isClassBound(string $id): bool;

    /**
     * Kiểm tra xem một ID có trạng thái singleton hay không.
     *
     * @param string $id Tên định danh.
     * @return bool `true` nếu ID có trạng thái singleton, `false` nếu không.
     *
     * @example
     * ```php
     * if ($container->isSingletonScoped('my.service')) {
     *     echo "my.service có trạng thái singleton.";
     * }
     * ```
     */
    function isSingletonScoped(string $id): bool;

    /**
     * Kiểm tra xem một ID có trạng thái transient hay không.
     *
     * @param string $id Tên định danh.
     * @return bool `true` nếu ID có trạng thái transient, `false` nếu không.
     *
     * @example
     * ```php
     * if ($container->isTransientScoped('my.service')) {
     *     echo "my.service có trạng thái transient.";
     * }
     * ```
     */
    function isTransientScoped(string $id): bool;
}
