import React, { useCallback, useEffect, useState } from 'react';
import { Table, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

export default function WishlistStats() {
    const [loading, setLoading] = useState(true);
    const [products, setProducts] = useState([]);

    const fetchStats = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/wishlist');
            setProducts(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được thống kê yêu thích.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchStats();
    }, [fetchStats]);

    return (
        <div>
            <PageHeader
                title="Sản phẩm yêu thích"
                description="Top sản phẩm được khách hàng yêu thích nhiều nhất."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: fetchStats },
                ]}
            />
            <Table
                rowKey="id"
                loading={loading}
                dataSource={products}
                pagination={false}
                columns={[
                    { title: 'Sản phẩm', dataIndex: 'name' },
                    { title: 'Danh mục', render: (_, item) => item.category?.name ?? '—' },
                    { title: 'Số lượt yêu thích', dataIndex: 'wishlists_count' },
                    { title: 'Giá từ', dataIndex: 'min_price', render: (v) => v ? Number(v).toLocaleString('vi-VN') + '₫' : '—' },
                ]}
            />
        </div>
    );
}
