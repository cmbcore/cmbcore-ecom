import React, { useCallback, useEffect, useState } from 'react';
import {
    Badge, Button, Card, Descriptions, Empty, Space,
    Table, Tag, Tooltip, Typography, message,
} from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

const { Title, Text } = Typography;

export default function ContactSubmissions() {
    const { formId } = useParams();
    const navigate = useNavigate();
    const [form, setForm] = useState(null);
    const [submissions, setSubmissions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, total: 0 });
    const [expanded, setExpanded] = useState(null);

    const fetchData = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const [formRes, subRes] = await Promise.all([
                api.get(`/contact-forms/${formId}`),
                api.get(`/contact-forms/${formId}/submissions?page=${page}`),
            ]);
            setForm(formRes.data.data);
            setSubmissions(subRes.data.data ?? []);
            setMeta(subRes.data.meta ?? {});
        } catch {
            message.error('Không thể tải dữ liệu.');
        } finally {
            setLoading(false);
        }
    }, [formId]);

    useEffect(() => { fetchData(); }, [fetchData]);

    const markRead = useCallback(async (id) => {
        await api.patch(`/contact-forms/submissions/${id}/read`);
        setSubmissions((prev) => prev.map((s) => s.id === id ? { ...s, is_read: true } : s));
    }, []);

    const formFields = form?.fields ?? [];

    const columns = [
        {
            title: '',
            dataIndex: 'is_read',
            key: 'read',
            width: 32,
            render: (read) => (
                <span style={{ display: 'inline-block', width: 8, height: 8, borderRadius: '50%', background: read ? 'transparent' : '#1677ff', border: read ? '1px solid #d9d9d9' : 'none' }} />
            ),
        },
        // Generate columns from form fields (show first 3)
        ...formFields.slice(0, 3).map((f) => ({
            title: f.label,
            key: f.name,
            ellipsis: true,
            render: (_, record) => (
                <Text style={{ fontSize: 13 }}>{record.data?.[f.name] ?? '—'}</Text>
            ),
        })),
        {
            title: 'Ngày gửi',
            dataIndex: 'created_at',
            key: 'created_at',
            width: 150,
            render: (d) => d ? new Date(d).toLocaleString('vi-VN') : '—',
        },
        {
            title: 'IP',
            dataIndex: 'ip_address',
            key: 'ip',
            width: 120,
            render: (ip) => <Text type="secondary" style={{ fontSize: 12 }}>{ip}</Text>,
        },
        {
            title: '',
            key: 'actions',
            width: 100,
            render: (_, record) => (
                <Space>
                    <Tooltip title="Xem chi tiết">
                        <Button
                            size="small"
                            icon={<FontIcon name="preview" />}
                            onClick={() => setExpanded(expanded === record.id ? null : record.id)}
                        />
                    </Tooltip>
                    {!record.is_read && (
                        <Tooltip title="Đánh dấu đã đọc">
                            <Button
                                size="small"
                                type="text"
                                icon={<FontIcon name="check" />}
                                onClick={() => markRead(record.id)}
                            />
                        </Tooltip>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 24 }}>
                <div>
                    <Button type="text" icon={<FontIcon name="move_up" />} onClick={() => navigate(-1)} style={{ marginBottom: 8 }}>
                        Quay lại
                    </Button>
                    <Title level={3} style={{ margin: 0 }}>
                        Submissions: {form?.name ?? '...'}
                    </Title>
                    <Text type="secondary">Tổng {meta.total} submissions</Text>
                </div>
            </div>

            <Card bordered={false}>
                <Table
                    rowKey="id"
                    dataSource={submissions}
                    columns={columns}
                    loading={loading}
                    rowClassName={(r) => r.is_read ? '' : 'ant-table-row--unread'}
                    expandable={{
                        expandedRowKeys: expanded ? [expanded] : [],
                        showExpandColumn: false,
                        expandedRowRender: (record) => (
                            <Descriptions
                                size="small"
                                bordered
                                column={2}
                                style={{ background: '#fff', margin: '0 16px 12px' }}
                            >
                                {Object.entries(record.data ?? {}).map(([key, value]) => (
                                    <Descriptions.Item
                                        key={key}
                                        label={formFields.find((f) => f.name === key)?.label ?? key}
                                    >
                                        {String(value)}
                                    </Descriptions.Item>
                                ))}
                                <Descriptions.Item label="URL trang">
                                    {record.page_url ?? '—'}
                                </Descriptions.Item>
                                <Descriptions.Item label="IP">
                                    {record.ip_address ?? '—'}
                                </Descriptions.Item>
                            </Descriptions>
                        ),
                    }}
                    locale={{ emptyText: <Empty description="Chưa có submission nào." image={Empty.PRESENTED_IMAGE_SIMPLE} /> }}
                    pagination={{
                        current: meta.current_page,
                        pageSize: 20,
                        total: meta.total,
                        onChange: fetchData,
                        showTotal: (total) => `${total} submissions`,
                    }}
                />
            </Card>
        </div>
    );
}
