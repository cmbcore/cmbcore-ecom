import React, { useCallback, useDeferredValue, useEffect, useState } from 'react';
import { Card, Input, Select, Space, Table, Tag, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

function formatDate(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('vi-VN', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function ActivityLogPage() {
    const [loading, setLoading] = useState(true);
    const [logs, setLogs] = useState([]);
    const [meta, setMeta] = useState({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        actions: [],
    });
    const [filters, setFilters] = useState({
        page: 1,
        search: '',
        action: undefined,
    });
    const deferredSearch = useDeferredValue(filters.search);

    const fetchLogs = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/activity-logs', {
                params: {
                    page: filters.page,
                    search: deferredSearch || undefined,
                    action: filters.action || undefined,
                },
            });

            setLogs(response.data.data ?? []);
            setMeta(response.data.meta ?? {
                current_page: 1,
                last_page: 1,
                per_page: 20,
                total: 0,
                actions: [],
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được nhật ký hoạt động.');
        } finally {
            setLoading(false);
        }
    }, [deferredSearch, filters.action, filters.page]);

    useEffect(() => {
        fetchLogs();
    }, [fetchLogs]);

    function updateFilter(name, value) {
        setFilters((current) => ({
            ...current,
            [name]: value,
            page: name === 'page' ? value : 1,
        }));
    }

    return (
        <div>
            <PageHeader
                title="Nhật ký hoạt động"
                description="Theo dõi admin đã thao tác gì, trên đối tượng nào và vào thời điểm nào."
            />

            <Card bordered={false} style={{ marginBottom: 16 }}>
                <Space wrap size={16}>
                    <Input
                        allowClear
                        value={filters.search}
                        onChange={(event) => updateFilter('search', event.target.value)}
                        placeholder="Tìm theo mô tả, đường dẫn, admin hoặc subject ID..."
                        style={{ width: 320 }}
                    />
                    <Select
                        allowClear
                        value={filters.action}
                        onChange={(value) => updateFilter('action', value)}
                        placeholder="Lọc theo loại thao tác"
                        style={{ width: 260 }}
                        options={(meta.actions ?? []).map((action) => ({ label: action, value: action }))}
                    />
                </Space>
            </Card>

            <Table
                rowKey="id"
                loading={loading}
                dataSource={logs}
                scroll={{ x: 1180 }}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => updateFilter('page', page),
                }}
                columns={[
                    {
                        title: 'Thời gian',
                        dataIndex: 'created_at',
                        width: 170,
                        render: (value) => formatDate(value),
                    },
                    {
                        title: 'Admin',
                        width: 220,
                        render: (_, item) => (
                            <div>
                                <strong>{item.actor?.name ?? 'Hệ thống'}</strong>
                                <div>{item.actor?.email ?? '-'}</div>
                            </div>
                        ),
                    },
                    {
                        title: 'Thao tác',
                        dataIndex: 'action',
                        width: 180,
                        render: (value) => <Tag color="blue">{value}</Tag>,
                    },
                    {
                        title: 'Mô tả',
                        dataIndex: 'description',
                        width: 280,
                        render: (value, item) => value || item.request_path,
                    },
                    {
                        title: 'Đối tượng',
                        width: 180,
                        render: (_, item) => item.subject_id
                            ? `${item.subject_type || 'resource'} #${item.subject_id}`
                            : (item.subject_type || '-'),
                    },
                    {
                        title: 'Request',
                        width: 220,
                        render: (_, item) => (
                            <Space direction="vertical" size={0}>
                                <Tag color={item.request_method === 'DELETE' ? 'red' : item.request_method === 'POST' ? 'green' : 'gold'}>
                                    {item.request_method}
                                </Tag>
                                <span>{item.request_path}</span>
                            </Space>
                        ),
                    },
                    {
                        title: 'Địa chỉ IP',
                        dataIndex: 'ip_address',
                        width: 140,
                        render: (value) => value || '-',
                    },
                ]}
            />
        </div>
    );
}
