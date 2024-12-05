<?php
namespace App\Constants;

enum SameSite: string
{
    /*Cookie sẽ được gửi bất kể yêu cầu có phải từ một miền khác hay không. 
    Tuy nhiên, nếu bạn sử dụng SameSite=None, cookie phải đi kèm với Secure (chỉ được gửi qua kết nối HTTPS).*/
    case NONE = 'None';
    /*Cookie được gửi khi người dùng thực hiện một hành động điều hướng (ví dụ: nhấp vào liên kết) từ một trang web khác
     về trang web của bạn, nhưng không được gửi trong các yêu cầu nội bộ như POST, PUT, DELETE, hoặc các yêu cầu AJAX.*/
    case LAX = 'Lax';
    /*Cookie chỉ được gửi khi yêu cầu đến từ cùng một trang web (cùng domain).*/
    case STRICT = 'Strict';

    public function formatToSend(): string {
        if ($this === SameSite::NONE) {
            /* Hack to make CHIPS: https://github.com/php/php-src/issues/12646 */
            return $this->value . '; Partitioned';
        }
        else {
            return $this->value;
        }
    }
}
