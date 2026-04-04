import React, { useCallback, useEffect, useState } from 'react';
import { Button, Input, Modal, Space, Table, Tag, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

export default function PaymentTransactions() {
    const [loading, setLoading] = useState(true);
    const [transactions, setTransactions] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, per_page: 20, total: 0 });
    const [search, setSearch] = useState('');

    const fetchTransactions = useCallback(async (page = 1) => {
        setLoading(true);

        try {
            const response = await api.get('/payments', { params: { page, search } });
            setTransactions(response.data.data ?? []);
            setMeta(response.data.meta ?? { current_page: 1, per_page: 20, total: 0 });
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được giao dịch.');
        } finally {
            setLoading(false);
        }
    }, [search]);

    useEffect(() => {
        fetchTransactions();
    }, [fetchTransactions]);

    async function confirmTransaction(id) {
        try {
            await api.post(`/payments/${id}/confirm`, {});
            message.success('Đã xác nhận thanh toán.');
            fetchTransactions(meta.current_page);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xác nhận được giao dịch.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Giao dịch thanh toán"
                description="Theo dõi giao dịch và xác nhận thu tiền thủ công."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: () => fetchTransactions(meta.current_page) },
                ]}
            />

            <Space style={{ marginBottom: 16 }}>
                <Input
                    allowClear
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder="Tìm theo số đơn, cổng thanh toán, mã giao dịch..."
                    style={{ width: 320 }}
                />
                <Button onClick={() => fetchTransactions(1)}>Tìm kiếm</Button>
            </Space>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={transactions}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => fetchTransactions(page),
                }}
                columns={[
                    { title: 'Mã đơn', render: (_, item) => item.order?.order_number ?? '—' },
                    { title: 'Cổng thanh toán', dataIndex: 'gateway' },
                    { title: 'Số tiền', dataIndex: 'amount', render: (v) => v ? Number(v).toLocaleString('vi-VN') + '₫' : '—' },
                    {
                        title: 'Trạng thái',
                        dataIndex: 'status',
                        render: (value) => {
                            const labels = { paid: 'Đã thanh toán', refunded: 'Hoàn tiền', pending: 'Chờ' };
                            const colors = { paid: 'green', refunded: 'orange', pending: 'blue' };
                            return <Tag color={colors[value] ?? 'default'}>{labels[value] ?? value}</Tag>;
                        },
                    },
                    { title: 'Mã tham chiếu', dataIndex: 'reference' },
                    {
                        title: 'Thao tác',
                        render: (_, item) => (
                            <Button
                                size="small"
                                disabled={item.status === 'paid'}
                                onClick={() => Modal.confirm({
                                    title: 'Xác nhận thanh toán?',
                                    content: 'Thao tác này sẽ đánh dấu giao dịch là đã thu tiền.',
                                    okText: 'Xác nhận',
                                    cancelText: 'Hủy',
                                    onOk: () => confirmTransaction(item.id),
                                })}
                            >
                                Xác nhận đã thu tiền
                            </Button>
                        ),
                    },
                ]}
            />
        </div>
    );
}
