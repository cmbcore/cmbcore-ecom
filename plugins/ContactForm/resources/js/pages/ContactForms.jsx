import React, { useCallback, useEffect, useState } from 'react';
import {
    Badge, Button, Card, Empty, message, Popconfirm,
    Space, Table, Tag, Tooltip, Typography,
} from 'antd';
import { useNavigate } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import FormBuilderModal from '../components/FormBuilderModal';

const { Title, Text } = Typography;

export default function ContactForms() {
    const { t } = useLocale();
    const navigate = useNavigate();
    const [forms, setForms] = useState([]);
    const [loading, setLoading] = useState(true);
    const [modalOpen, setModalOpen] = useState(false);
    const [editingForm, setEditingForm] = useState(null);

    const fetchForms = useCallback(async () => {
        setLoading(true);
        try {
            const res = await api.get('/contact-forms/list');
            setForms(res.data.data ?? []);
        } catch {
            message.error('Không thể tải danh sách form.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { fetchForms(); }, [fetchForms]);

    const handleDelete = useCallback(async (id) => {
        try {
            await api.delete(`/contact-forms/${id}`);
            message.success('Đã xóa form.');
            setForms((prev) => prev.filter((f) => f.id !== id));
        } catch {
            message.error('Không thể xóa form.');
        }
    }, []);

    const handleSaved = useCallback(() => {
        setModalOpen(false);
        setEditingForm(null);
        fetchForms();
    }, [fetchForms]);

    const openCreate = useCallback(() => {
        setEditingForm(null);
        setModalOpen(true);
    }, []);

    const openEdit = useCallback((form) => {
        setEditingForm(form);
        setModalOpen(true);
    }, []);

    const columns = [
        {
            title: 'Tên form',
            dataIndex: 'name',
            key: 'name',
            render: (name, record) => (
                <Space direction="vertical" size={0}>
                    <Text strong>{name}</Text>
                    <Text type="secondary" style={{ fontSize: 12 }}>/{record.slug}</Text>
                </Space>
            ),
        },
        {
            title: 'Số trường',
            dataIndex: 'field_count',
            key: 'field_count',
            width: 110,
            render: (n) => <Tag color="blue">{n} trường</Tag>,
        },
        {
            title: 'Submissions',
            dataIndex: 'submission_count',
            key: 'submission_count',
            width: 130,
            render: (n) => (
                <Badge count={n} showZero overflowCount={999} style={{ backgroundColor: n > 0 ? '#52c41a' : '#d9d9d9' }} />
            ),
        },
        {
            title: 'Trạng thái',
            dataIndex: 'is_active',
            key: 'is_active',
            width: 110,
            render: (active) => active
                ? <Tag color="success">Hoạt động</Tag>
                : <Tag color="default">Tắt</Tag>,
        },
        {
            title: 'Thao tác',
            key: 'actions',
            width: 200,
            render: (_, record) => (
                <Space>
                    <Tooltip title="Xem submissions">
                        <Button
                            size="small"
                            icon={<FontIcon name="preview" />}
                            onClick={() => navigate(`/admin/plugins/contact-form/${record.id}/submissions`)}
                        >
                            Submissions
                        </Button>
                    </Tooltip>
                    <Button size="small" icon={<FontIcon name="edit" />} onClick={() => openEdit(record)}>
                        Sửa
                    </Button>
                    <Popconfirm
                        title="Xóa form này?"
                        description="Tất cả submissions liên quan cũng sẽ bị xóa."
                        onConfirm={() => handleDelete(record.id)}
                        okText="Xóa"
                        cancelText="Hủy"
                        okButtonProps={{ danger: true }}
                    >
                        <Button size="small" danger icon={<FontIcon name="delete" />} />
                    </Popconfirm>
                </Space>
            ),
        },
    ];

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 24 }}>
                <div>
                    <Title level={3} style={{ margin: 0 }}>
                        <FontIcon name="plugin" style={{ marginRight: 8 }} />
                        Quản lý Form liên hệ
                    </Title>
                    <Text type="secondary">Tạo nhiều form với cấu hình trường tùy chỉnh, nhúng vào trang bằng Page Builder.</Text>
                </div>
                <Button type="primary" icon={<FontIcon name="create" />} onClick={openCreate} size="large">
                    Tạo form mới
                </Button>
            </div>

            <Card bordered={false}>
                <Table
                    rowKey="id"
                    dataSource={forms}
                    columns={columns}
                    loading={loading}
                    locale={{ emptyText: <Empty description="Chưa có form nào. Hãy tạo form đầu tiên!" image={Empty.PRESENTED_IMAGE_SIMPLE} /> }}
                    pagination={{ pageSize: 15, hideOnSinglePage: true }}
                />
            </Card>

            <FormBuilderModal
                open={modalOpen}
                initialData={editingForm}
                onClose={() => { setModalOpen(false); setEditingForm(null); }}
                onSaved={handleSaved}
            />
        </div>
    );
}
