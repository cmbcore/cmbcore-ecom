import React, { useCallback, useEffect, useState } from 'react';
import { Table, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

export default function SearchAnalytics() {
    const [loading, setLoading] = useState(true);
    const [rows, setRows] = useState([]);

    const fetchAnalytics = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/search/analytics');
            setRows(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được phân tích tìm kiếm.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchAnalytics();
    }, [fetchAnalytics]);

    return (
        <div>
            <PageHeader
                title="Phân tích tìm kiếm"
                description="Top từ khóa được tìm nhiều nhất trên storefront."
            />
            <Table
                rowKey="term"
                loading={loading}
                dataSource={rows}
                pagination={false}
                columns={[
                    { title: 'Từ khóa', dataIndex: 'term' },
                    { title: 'Số lượt tìm', dataIndex: 'hits' },
                    { title: 'Lần tìm gần nhất', dataIndex: 'last_searched_at' },
                ]}
            />
        </div>
    );
}
