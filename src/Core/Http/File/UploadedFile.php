<?php
namespace App\Core\Http\File;

use App\Constants\MimeType;
use App\Utils\Files;
use App\Utils\Paths;
use App\Utils\Randoms;

/**
 * Lớp UploadedFile đại diện cho một tệp tin đã được tải lên.
 *
 * @namespace App\Core\Http\File
 * @description
 * Lớp này mở rộng từ `SplFileInfo` để quản lý tệp tin tải lên trong ứng dụng PHP.
 * Nó cung cấp các phương thức để kiểm tra tính hợp lệ, lấy thông tin về tệp tin, 
 * và lưu trữ nó vào thư mục mong muốn.
 *
 * @example
 * ```php
 * use App\Core\Http\File\UploadedFile;
 *
 * // Khởi tạo một tệp tin tải lên
 * $file = new UploadedFile($_FILES['file']['tmp_name'], $_FILES['file']['name'], $_FILES['file']['type'], $_FILES['file']['error']);
 *
 * // Kiểm tra tính hợp lệ
 * if (!$file->isValid()) {
 *     echo "File upload failed with error: " . $file->getError();
 * }
 *
 * // Lưu trữ tệp tin
 * $storedPath = $file->store('/uploads/');
 * if ($storedPath !== false) {
 *     echo "File stored at: " . $storedPath;
 * } else {
 *     echo "Failed to store file.";
 * }
 * ```
 */
class UploadedFile extends \SplFileInfo
{
    private string $originalName;
    private string $mimeType;
    private int $error;
    private bool $stored = false;
    private ?string $storedPath = null;

    /**
     * Khởi tạo đối tượng UploadedFile.
     *
     * @param string $tmpPath Đường dẫn tạm thời của tệp tin tải lên.
     * @param string $originalName Tên gốc của tệp tin.
     * @param string|null $mimeType Loại MIME của tệp tin (mặc định: `application/octet-stream`).
     * @param int|null $error Mã lỗi tải lên (mặc định: `UPLOAD_ERR_OK`).
     */
    public function __construct(
        string $tmpPath,
        string $originalName,
        string $mimeType = null,
        int $error = null,
    ) {
        parent::__construct($tmpPath);

        $this->originalName = Files::getName($originalName);
        $this->mimeType = $mimeType ?? MimeType::APPLICATION_OCTET_STREAM;
        $this->error = $error ?? \UPLOAD_ERR_OK;
    }

    /**
     * Kiểm tra xem tệp tin có hợp lệ không (không bị lỗi tải lên).
     *
     * @return bool `true` nếu tệp tin hợp lệ, `false` nếu không hợp lệ.
     */
    public function isValid(): bool {
        return $this->error === \UPLOAD_ERR_OK;
    }

    /**
     * Lấy mã lỗi tải lên của tệp tin.
     *
     * @return int Mã lỗi tải lên (`UPLOAD_ERR_*`).
     */
    public function getError(): int {
        return $this->error;
    }

     /**
     * Lấy tên gốc của tệp tin.
     *
     * @return string Tên tệp tin do người dùng tải lên.
     */
    public function getClientOriginalName(): string {
        return $this->originalName;
    }

   /**
     * Lấy phần mở rộng của tệp tin gốc.
     *
     * @return string Phần mở rộng của tệp tin.
     */
    public function getClientOriginalExtension(): string {
        return Paths::getExtension($this->originalName);
    }

    /**
     * Lấy loại MIME của tệp tin.
     *
     * @return string Loại MIME của tệp tin.
     */
    public function getClientMimeType(): string {
        return $this->mimeType;
    }

    /**
     * Lấy nội dung của tệp tin.
     *
     * @return string|false Nội dung tệp tin hoặc `false` nếu không thể đọc.
     */
    public function getContent(): string|false  {
        $path = $this->storedPath ?? $this->getRealPath();
        if (!$path) {
            return false;
        }
        return file_get_contents($path);
    }

    /**
     * Lưu tệp tin vào một thư mục với một tên cụ thể.
     *
     * @param string $storingPath Đường dẫn lưu trữ.
     * @param string $name Tên tệp tin khi lưu.
     * @return string|false Đường dẫn đã lưu hoặc `false` nếu thất bại.
     */
    public function storeAs(string $storingPath, string $name): string|false {
        if ($this->stored || !$tmpPath = $this->getRealPath()) {
            return false;
        }

        return $this->storeImpl($tmpPath, $storingPath, $name);
    }

    /**
     * Lưu tệp tin vào một thư mục với một tên ngẫu nhiên.
     *
     * @param string $storingPath Đường dẫn lưu trữ.
     * @return string|false Đường dẫn đã lưu hoặc `false` nếu thất bại.
     */
    public function store(string $storingPath): string|false {
        if ($this->stored || !$tmpPath = $this->getRealPath()) {
            return false;
        }
        
        $name = static::generateRandomUniqueFileName();
        $ext =  Files::getFileExtension($tmpPath);
        if ($ext) {
            $name .= '.' . $ext;
        }

        return $this->storeImpl($tmpPath, $storingPath, $name);
    }

    /**
     * Tạo một tên tệp tin ngẫu nhiên duy nhất.
     *
     * @return string Tên tệp tin ngẫu nhiên.
     */
    private static function generateRandomUniqueFileName() {
        return date("Y-m-d-H-i-s") . '_' . Randoms::uuidv4();
    }

    /**
     * Thực hiện lưu tệp tin vào thư mục đích.
     *
     * @param string $tmpPath Đường dẫn tạm thời của tệp tin.
     * @param string $storingPath Đường dẫn lưu trữ.
     * @param string $name Tên tệp tin khi lưu.
     * @return string|false Đường dẫn đã lưu hoặc `false` nếu thất bại.
     */
    private function storeImpl(string $tmpPath, string $storingPath, string $name): string|false {
        if (!Files::createDirectory($storingPath)) {
            return false;
        }
        
        $storingPath = Paths::normalize($storingPath);
        $dst = $storingPath . $name;
        $success = move_uploaded_file($tmpPath, $dst);
        if ($success) {
            $this->stored = true;
            $this->storedPath = $dst;
            return $dst;
        }
        else {
            return false;
        }
    }
}
