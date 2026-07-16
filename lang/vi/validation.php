<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Bản dịch tiếng Việt cho các thông báo lỗi mặc định của Laravel Validator.
    | Nếu không có file này, Laravel sẽ rơi về locale dự phòng (en) và hiển
    | thị thông báo lỗi bằng tiếng Anh dù APP_LOCALE=vi.
    |
    */

    'accepted' => ':attribute phải được chấp nhận.',
    'accepted_if' => ':attribute phải được chấp nhận khi :other là :value.',
    'active_url' => ':attribute không phải là URL hợp lệ.',
    'after' => ':attribute phải là ngày sau ngày :date.',
    'after_or_equal' => ':attribute phải là ngày sau hoặc bằng ngày :date.',
    'alpha' => ':attribute chỉ được chứa chữ cái.',
    'alpha_dash' => ':attribute chỉ được chứa chữ cái, số, dấu gạch ngang và gạch dưới.',
    'alpha_num' => ':attribute chỉ được chứa chữ cái và số.',
    'array' => ':attribute phải là một mảng.',
    'ascii' => ':attribute chỉ được chứa ký tự và ký hiệu đơn byte.',
    'before' => ':attribute phải là ngày trước ngày :date.',
    'before_or_equal' => ':attribute phải là ngày trước hoặc bằng ngày :date.',
    'between' => [
        'array' => ':attribute phải có từ :min đến :max phần tử.',
        'file' => ':attribute phải có dung lượng từ :min đến :max kilobytes.',
        'numeric' => ':attribute phải có giá trị từ :min đến :max.',
        'string' => ':attribute phải có độ dài từ :min đến :max ký tự.',
    ],
    'boolean' => ':attribute phải có giá trị đúng hoặc sai.',
    'can' => ':attribute chứa giá trị không được phép.',
    'confirmed' => ':attribute xác nhận không khớp.',
    'current_password' => 'Mật khẩu không chính xác.',
    'date' => ':attribute không phải là ngày hợp lệ.',
    'date_equals' => ':attribute phải là ngày bằng ngày :date.',
    'date_format' => ':attribute không đúng định dạng :format.',
    'decimal' => ':attribute phải có :decimal chữ số thập phân.',
    'declined' => ':attribute phải bị từ chối.',
    'declined_if' => ':attribute phải bị từ chối khi :other là :value.',
    'different' => ':attribute và :other phải khác nhau.',
    'digits' => ':attribute phải có :digits chữ số.',
    'digits_between' => ':attribute phải có từ :min đến :max chữ số.',
    'dimensions' => ':attribute có kích thước ảnh không hợp lệ.',
    'distinct' => ':attribute bị trùng lặp giá trị.',
    'doesnt_end_with' => ':attribute không được kết thúc bằng một trong các giá trị: :values.',
    'doesnt_start_with' => ':attribute không được bắt đầu bằng một trong các giá trị: :values.',
    'email' => ':attribute phải là địa chỉ email hợp lệ.',
    'ends_with' => ':attribute phải kết thúc bằng một trong các giá trị: :values.',
    'enum' => ':attribute đã chọn không hợp lệ.',
    'exists' => ':attribute đã chọn không hợp lệ.',
    'extensions' => ':attribute phải có một trong các đuôi file: :values.',
    'file' => ':attribute phải là một file.',
    'filled' => ':attribute phải có giá trị.',
    'gt' => [
        'array' => ':attribute phải có nhiều hơn :value phần tử.',
        'file' => ':attribute phải lớn hơn :value kilobytes.',
        'numeric' => ':attribute phải lớn hơn :value.',
        'string' => ':attribute phải dài hơn :value ký tự.',
    ],
    'gte' => [
        'array' => ':attribute phải có tối thiểu :value phần tử.',
        'file' => ':attribute phải lớn hơn hoặc bằng :value kilobytes.',
        'numeric' => ':attribute phải lớn hơn hoặc bằng :value.',
        'string' => ':attribute phải dài từ :value ký tự trở lên.',
    ],
    'hex_color' => ':attribute phải là mã màu hex hợp lệ.',
    'image' => ':attribute phải là một hình ảnh.',
    'in' => ':attribute đã chọn không hợp lệ.',
    'in_array' => ':attribute phải tồn tại trong :other.',
    'integer' => ':attribute phải là số nguyên.',
    'ip' => ':attribute phải là địa chỉ IP hợp lệ.',
    'ipv4' => ':attribute phải là địa chỉ IPv4 hợp lệ.',
    'ipv6' => ':attribute phải là địa chỉ IPv6 hợp lệ.',
    'json' => ':attribute phải là chuỗi JSON hợp lệ.',
    'lowercase' => ':attribute phải là chữ thường.',
    'lt' => [
        'array' => ':attribute phải có ít hơn :value phần tử.',
        'file' => ':attribute phải nhỏ hơn :value kilobytes.',
        'numeric' => ':attribute phải nhỏ hơn :value.',
        'string' => ':attribute phải ngắn hơn :value ký tự.',
    ],
    'lte' => [
        'array' => ':attribute không được có nhiều hơn :value phần tử.',
        'file' => ':attribute phải nhỏ hơn hoặc bằng :value kilobytes.',
        'numeric' => ':attribute phải nhỏ hơn hoặc bằng :value.',
        'string' => ':attribute không được dài hơn :value ký tự.',
    ],
    'mac_address' => ':attribute phải là địa chỉ MAC hợp lệ.',
    'max' => [
        'array' => ':attribute không được có nhiều hơn :max phần tử.',
        'file' => ':attribute không được lớn hơn :max kilobytes.',
        'numeric' => ':attribute không được lớn hơn :max.',
        'string' => ':attribute không được dài hơn :max ký tự.',
    ],
    'max_digits' => ':attribute không được có nhiều hơn :max chữ số.',
    'mimes' => ':attribute phải là file thuộc loại: :values.',
    'mimetypes' => ':attribute phải là file thuộc loại: :values.',
    'min' => [
        'array' => ':attribute phải có tối thiểu :min phần tử.',
        'file' => ':attribute phải có dung lượng tối thiểu :min kilobytes.',
        'numeric' => ':attribute phải có giá trị tối thiểu :min.',
        'string' => ':attribute phải có tối thiểu :min ký tự.',
    ],
    'min_digits' => ':attribute phải có tối thiểu :min chữ số.',
    'missing' => ':attribute phải không được điền.',
    'missing_if' => ':attribute phải không được điền khi :other là :value.',
    'missing_unless' => ':attribute phải không được điền trừ khi :other là :value.',
    'missing_with' => ':attribute phải không được điền khi có :values.',
    'missing_with_all' => ':attribute phải không được điền khi có :values.',
    'multiple_of' => ':attribute phải là bội số của :value.',
    'not_in' => ':attribute đã chọn không hợp lệ.',
    'not_regex' => 'Định dạng :attribute không hợp lệ.',
    'numeric' => ':attribute phải là một số.',
    'password' => [
        'letters' => ':attribute phải chứa ít nhất một chữ cái.',
        'mixed' => ':attribute phải chứa ít nhất một chữ hoa và một chữ thường.',
        'numbers' => ':attribute phải chứa ít nhất một chữ số.',
        'symbols' => ':attribute phải chứa ít nhất một ký tự đặc biệt.',
        'uncompromised' => ':attribute đã xuất hiện trong một vụ rò rỉ dữ liệu. Vui lòng chọn :attribute khác.',
    ],
    'present' => ':attribute phải có mặt.',
    'present_if' => ':attribute phải có mặt khi :other là :value.',
    'present_unless' => ':attribute phải có mặt trừ khi :other là :value.',
    'present_with' => ':attribute phải có mặt khi có :values.',
    'present_with_all' => ':attribute phải có mặt khi có :values.',
    'prohibited' => ':attribute bị cấm.',
    'prohibited_if' => ':attribute bị cấm khi :other là :value.',
    'prohibited_unless' => ':attribute bị cấm trừ khi :other nằm trong :values.',
    'prohibits' => ':attribute cấm :other xuất hiện.',
    'regex' => 'Định dạng :attribute không hợp lệ.',
    'required' => ':attribute là bắt buộc.',
    'required_array_keys' => ':attribute phải chứa các mục: :values.',
    'required_if' => ':attribute là bắt buộc khi :other là :value.',
    'required_if_accepted' => ':attribute là bắt buộc khi :other được chấp nhận.',
    'required_unless' => ':attribute là bắt buộc trừ khi :other nằm trong :values.',
    'required_with' => ':attribute là bắt buộc khi có :values.',
    'required_with_all' => ':attribute là bắt buộc khi có :values.',
    'required_without' => ':attribute là bắt buộc khi không có :values.',
    'required_without_all' => ':attribute là bắt buộc khi không có bất kỳ giá trị nào trong :values.',
    'same' => ':attribute và :other phải khớp nhau.',
    'size' => [
        'array' => ':attribute phải chứa :size phần tử.',
        'file' => ':attribute phải có dung lượng :size kilobytes.',
        'numeric' => ':attribute phải có giá trị :size.',
        'string' => ':attribute phải có độ dài :size ký tự.',
    ],
    'starts_with' => ':attribute phải bắt đầu bằng một trong các giá trị: :values.',
    'string' => ':attribute phải là một chuỗi ký tự.',
    'timezone' => ':attribute phải là múi giờ hợp lệ.',
    'unique' => ':attribute đã tồn tại.',
    'uploaded' => 'Tải lên :attribute thất bại.',
    'uppercase' => ':attribute phải là chữ hoa.',
    'url' => ':attribute phải là URL hợp lệ.',
    'ulid' => ':attribute phải là ULID hợp lệ.',
    'uuid' => ':attribute phải là UUID hợp lệ.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'product_sku_id' => [
            'required' => 'Vui lòng chọn phân loại sản phẩm (thuộc tính) trước khi tiếp tục.',
            'exists' => 'Phân loại sản phẩm đã chọn hiện không khả dụng.',
        ],
        'quantity' => [
            'required' => 'Vui lòng nhập số lượng.',
            'min' => 'Số lượng phải lớn hơn hoặc bằng :min.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'product_sku_id' => 'phân loại sản phẩm',
        'quantity' => 'số lượng',
        'customer_name' => 'họ tên khách hàng',
        'customer_phone' => 'số điện thoại',
        'guest_email' => 'địa chỉ email',
        'address_id' => 'địa chỉ giao hàng',
        'shipping_method_id' => 'phương thức vận chuyển',
        'coupon_code' => 'mã giảm giá',
        'payment_method' => 'phương thức thanh toán',
        'recipient_name' => 'tên người nhận',
        'shipping_phone' => 'số điện thoại người nhận',
        'province' => 'tỉnh/thành phố',
        'district' => 'quận/huyện',
        'ward' => 'phường/xã',
        'address_line' => 'địa chỉ chi tiết',
        'address_note' => 'ghi chú địa chỉ',
        'note' => 'ghi chú đơn hàng',
        'email' => 'địa chỉ email',
        'password' => 'mật khẩu',
        'name' => 'họ tên',
        'phone' => 'số điện thoại',
        'title' => 'tiêu đề',
        'content' => 'nội dung',
        'rating' => 'số sao đánh giá',
    ],

];
