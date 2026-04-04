import React, { useCallback, useDeferredValue, useEffect, useState } from 'react';
import { Button, Card, Input, Space, Table, Tag, message } from 'antd';
import { useNavigate } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

function formatDate(value, locale) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale === 'vi' ? 'vi-VN' : 'en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function CustomerList() {
    const navigate = useNavigate();
    const { currentLocale, t } = useLocale();
    const [loading, setLoading] = useState(true);
    const [customers, setCustomers] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });
    const [filters, setFilters] = useState({
        search: '',
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

    const fetchCustomers = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/customers', {
                params: {
                    search: deferredSearch || undefined,
                    page: filters.page,
                },
            });

            setCustomers(response.data.data ?? []);
            setMeta(response.data.meta ?? {
                current_page: 1,
                last_page: 1,
                per_page: 15,
                total: 0,
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('customers.messages.load_failed'));
        } finally {
            setLoading(false);
        }
    }, [deferredSearch, filters.page, t]);

    useEffect(() => {
        fetchCustomers();
    }, [fetchCustomers]);

    return (
        <div>
            <PageHeader
                title={t('customers.title')}
                description={t('customers.description')}
                extra={[
                    { label: t('customers.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchCustomers },
                ]}
            />

            <Card bordered={false}>
                <Space wrap size={16}>
                    <Input
                        allowClear
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                        placeholder={t('customers.placeholders.search')}
                        style={{ width: 280 }}
                    />
                </Space>
            </Card>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={customers}
                scroll={{ x: 980 }}
                columns={[
                    {
                        title: t('customers.table.customer'),
                        dataIndex: 'name',
                        width: 220,
                        render: (_, customer) => (
                            <div>
                                <strong>{customer.name}</strong>
                                <div>{customer.email || '-'}</div>
                            </div>
                        ),
                    },
                    {
                        title: t('customers.table.phone'),
                        dataIndex: 'phone',
                        width: 160,
                        render: (value) => value || '-',
                    },
                    {
                        title: t('customers.table.status'),
                        dataIndex: 'is_active',
                        width: 120,
                        render: (value) => (
                            <Tag color={value ? 'success' : 'default'}>
                                {value ? t('common.status_labels.active') : t('common.status_labels.inactive')}
                            </Tag>
                        ),
                    },
                    {
                        title: t('customers.table.address_count'),
                        dataIndex: 'address_count',
                        width: 120,
                    },
                    {
                        title: t('customers.table.order_count'),
                        dataIndex: 'order_count',
                        width: 120,
                    },
                    {
                        title: t('customers.table.created_at'),
                        dataIndex: 'created_at',
                        width: 180,
                        render: (value) => formatDate(value, currentLocale),
                    },
                    {
                        title: t('customers.table.actions'),
                        key: 'actions',
                        width: 120,
                        fixed: 'right',
                        render: (_, customer) => (
                            <Button size="small" icon={<FontIcon name="eye" />} onClick={() => navigate(`/admin/customers/${customer.id}`)}>
                                {t('customers.actions.view')}
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
