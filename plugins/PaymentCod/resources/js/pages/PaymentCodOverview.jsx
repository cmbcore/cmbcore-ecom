import React from 'react';
import { Card, Typography } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';

const { Paragraph } = Typography;

export default function PaymentCodOverview() {
    return (
        <div>
            <PageHeader
                title="COD Gateway"
                description="Gateway COD đang được đăng ký qua plugin payment-cod."
            />
            <Card bordered={false}>
                <Paragraph>Gateway này xử lý đơn hàng thanh toán khi nhận hàng và tạo transaction cho admin xác nhận thu tiền.</Paragraph>
                <Paragraph>Bạn có thể bật/tắt và sửa hướng dẫn hiển thị trong Plugin Manager.</Paragraph>
            </Card>
        </div>
    );
}
