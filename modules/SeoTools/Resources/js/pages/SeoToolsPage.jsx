import React, { useCallback, useEffect, useState } from 'react';
import { Card, Typography, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

const { Paragraph } = Typography;

export default function SeoToolsPage() {
    const [payload, setPayload] = useState(null);

    const fetchPayload = useCallback(async () => {
        try {
            const response = await api.get('/seo-tools');
            setPayload(response.data.data ?? null);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được dữ liệu SEO.');
        }
    }, []);

    useEffect(() => {
        fetchPayload();
    }, [fetchPayload]);

    return (
        <div>
            <PageHeader
                title="Công cụ SEO"
                description="Sitemap và robots được đồng bộ từ cài đặt storefront."
            />
            <Card bordered={false}>
                <Paragraph><strong>Sitemap:</strong> {payload?.sitemap_url}</Paragraph>
                <Paragraph><strong>Robots.txt:</strong> {payload?.robots_path}</Paragraph>
                <Paragraph><strong>Nội dung hiện tại:</strong></Paragraph>
                <pre>{payload?.robots_content}</pre>
            </Card>
        </div>
    );
}
