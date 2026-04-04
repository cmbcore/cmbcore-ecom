import React, { useCallback, useDeferredValue, useEffect, useState } from 'react';
import { Button, Card, Input, Select, Space, Table, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

function formatCurrency(value, locale) {
    return new Intl.NumberFormat(locale === 'vi' ? 'vi-VN' : 'en-US', {
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function formatDate(value, locale) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale === 'vi' ? 'vi-VN' : 'en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function OrderList() {
    const navigate = useNavigate();
    const { currentLocale, t } = useLocale();
    const [loading, setLoading] = useState(true);
    const [orders, setOrders] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        search: '',
        order_status: undefined,
        fulfillment_status: undefined,
        page: 1,
    });
    const deferredSearch = useDeferredValue(filters.search);

    function updateFilter(name, value) {
        setFilters((current) => ({
            ...current,
            [name]: value,
            page: name === 'page' ? value : 1,
        }));
    }

    const fetchOrders = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/orders', {
                params: {
                    search: deferredSearch || undefined,
                    order_status: filters.order_status,
                    fulfillment_status: filters.fulfillment_status,
                    page: filters.page,
                },
            });

            setOrders(response.data.data ?? []);
            setMeta(response.data.meta ?? {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('orders.messages.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [deferredSearch, filters.fulfillment_status, filters.order_status, filters.page, t]);

    useEffect(() => {
        fetchOrders();
    }, [fetchOrders]);

    return (
        <div>
            <PageHeader
                title={t('orders.title')}
                description={t('orders.description')}
                extra={[
                    { label: t('orders.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchOrders },
                ]}
            />

            <Card bordered={false}>
                <Space wrap size={16}>
                    <Input
                        allowClear
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                        placeholder={t('orders.placeholders.search')}
                        style={{ width: 280 }}
                    />
                    <Select
                        allowClear
                        value={filters.order_status}
                        onChange={(value) => updateFilter('order_status', value)}
                        placeholder={t('orders.placeholders.order_status')}
                        style={{ width: 200 }}
                        options={[
                            { label: t('common.status_labels.pending'), value: 'pending' },
                            { label: t('common.status_labels.confirmed'), value: 'confirmed' },
                            { label: t('common.status_labels.cancelled'), value: 'cancelled' },
                        ]}
                    />
                    <Select
                        allowClear
                        value={filters.fulfillment_status}
                        onChange={(value) => updateFilter('fulfillment_status', value)}
                        placeholder={t('orders.placeholders.fulfillment_status')}
                        style={{ width: 220 }}
                        options={[
                            { label: t('common.status_labels.pending'), value: 'pending' },
                            { label: t('common.status_labels.processing'), value: 'processing' },
                            { label: t('common.status_labels.shipping'), value: 'shipping' },
                            { label: t('common.status_labels.delivered'), value: 'delivered' },
                        ]}
                    />
                </Space>
            </Card>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={orders}
                scroll={{ x: 1250 }}
                columns={[
                    {
                        title: t('orders.table.order'),
                        dataIndex: 'order_number',
                        width: 180,
                        render: (_, order) => (
                            <div>
                                <strong>{order.order_number}</strong>
                                <div>{order.source}</div>
                            </div>
                        ),
                    },
                    {
                        title: t('orders.table.customer'),
                        dataIndex: 'customer_name',
                        width: 220,
                        render: (_, order) => (
                            <div>
                                <strong>{order.customer_name}</strong>
                                <div>{order.customer_phone}</div>
                            </div>
                        ),
                    },
                    {
                        title: t('orders.table.order_status'),
                        dataIndex: 'order_status',
                        width: 140,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('orders.table.fulfillment_status'),
                        dataIndex: 'fulfillment_status',
                        width: 160,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('orders.table.payment_status'),
                        dataIndex: 'payment_status',
                        width: 150,
                        render: (value) => <StatusBadge value={value} />,
                    },
                    {
                        title: t('orders.table.items'),
                        dataIndex: 'items_count',
                        width: 80,
                    },
                    {
                        title: t('orders.table.total'),
                        dataIndex: 'grand_total',
                        width: 140,
                        render: (value) => formatCurrency(value, currentLocale),
                    },
                    {
                        title: t('orders.table.created_at'),
                        dataIndex: 'created_at',
                        width: 180,
                        render: (value) => formatDate(value, currentLocale),
                    },
                    {
                        title: t('orders.table.actions'),
                        key: 'actions',
                        width: 120,
                        fixed: 'right',
                        render: (_, order) => (
                            <Button size="small" icon={<FontIcon name="eye" />} onClick={() => navigate(`/admin/orders/${order.id}`)}>
                                {t('orders.actions.view')}
                            </Button>
                        ),
                    },
                ]}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => updateFilter('page', page),
                }}
            />
        </div>
    );
}
