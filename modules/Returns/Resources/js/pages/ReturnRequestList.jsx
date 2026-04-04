import React, { useCallback, useEffect, useState } from 'react';
import { Button, Modal, Space, Table, Tag, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

const STATUS_LABELS = {
    pending: 'Chờ duyệt',
    approved: 'Đã duyệt',
    rejected: 'Từ chối',
    refunded: 'Đã hoàn tiền',
};
const STATUS_COLORS = { pending: 'orange', approved: 'blue', rejected: 'red', refunded: 'green' };

export default function ReturnRequestList() {
    const [loading, setLoading] = useState(true);
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, per_page: 20, total: 0 });

    const fetchItems = useCallback(async (page = 1) => {
        setLoading(true);

        try {
            const response = await api.get('/returns', { params: { page } });
            setItems(response.data.data ?? []);
            setMeta(response.data.meta ?? { current_page: 1, per_page: 20, total: 0 });
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được yêu cầu đổi trả.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchItems();
    }, [fetchItems]);

    async function updateStatus(item, status) {
        try {
            await api.put(`/returns/${item.id}`, { status, refund_amount: item.refund_amount });
            message.success('Đã cập nhật yêu cầu.');
            fetchItems(meta.current_page);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không cập nhật được yêu cầu.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Đổi trả hàng"
                description="Duyệt yêu cầu đổi trả và theo dõi hoàn tiền."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: () => fetchItems(meta.current_page) },
                ]}
            />
            <Table
                rowKey="id"
                loading={loading}
                dataSource={items}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => fetchItems(page),
                }}
                columns={[
                    { title: 'Mã đơn', render: (_, item) => item.order?.order_number ?? '—' },
                    { title: 'Khách hàng', render: (_, item) => item.user?.name ?? item.order?.customer_name ?? '—' },
                    { title: 'Lý do', dataIndex: 'reason' },
                    { title: 'Số lượng', dataIndex: 'requested_quantity' },
                    { title: 'Hoàn tiền', dataIndex: 'refund_amount', render: (v) => v ? Number(v).toLocaleString('vi-VN') + '₫' : '—' },
                    {
                        title: 'Trạng thái',
                        dataIndex: 'status',
                        render: (v) => <Tag color={STATUS_COLORS[v] ?? 'default'}>{STATUS_LABELS[v] ?? v}</Tag>,
                    },
                    {
                        title: 'Thao tác',
                        render: (_, item) => (
                            <Space>
                                <Button size="small" onClick={() => updateStatus(item, 'approved')}>Duyệt</Button>
                                <Button size="small" danger onClick={() => updateStatus(item, 'rejected')}>Từ chối</Button>
                                <Button
                                    size="small"
                                    onClick={() => Modal.confirm({
                                        title: 'Đánh dấu đã hoàn tiền?',
                                        okText: 'Xác nhận',
                                        cancelText: 'Hủy',
                                        onOk: () => updateStatus(item, 'refunded'),
                                    })}
                                >
                                    Đã hoàn tiền
                                </Button>
                            </Space>
                        ),
                    },
                ]}
            />
        </div>
    );
}
