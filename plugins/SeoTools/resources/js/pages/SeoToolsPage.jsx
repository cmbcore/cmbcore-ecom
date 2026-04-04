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
            message.error(error.response?.data?.message ?? 'Khong tai duoc SEO tools.');
        }
    }, []);

    useEffect(() => {
        fetchPayload();
    }, [fetchPayload]);

    return (
        <div>
            <PageHeader
                title="SEO Tools"
                description="Sitemap va robots duoc dong bo tu cai dat storefront thong qua plugin."
            />
            <Card bordered={false}>
                <Paragraph><strong>Sitemap:</strong> {payload?.sitemap_url}</Paragraph>
                <Paragraph><strong>Robots.txt:</strong> {payload?.robots_path}</Paragraph>
                <Paragraph><strong>Noi dung hien tai:</strong></Paragraph>
                <pre>{payload?.robots_content}</pre>
            </Card>
        </div>
    );
}
