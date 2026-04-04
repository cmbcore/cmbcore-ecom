import React, { useCallback, useEffect, useState } from 'react';
import { Button, Form, Input, Modal, Space, Switch, Table, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

function TemplateModal({ open, initialValues, onCancel, onSubmit }) {
    const [form] = Form.useForm();

    return (
        <Modal open={open} title="Sửa mẫu thông báo" onCancel={onCancel} onOk={() => form.submit()} okText="Lưu thay đổi" cancelText="Hủy" destroyOnHidden width={760}>
            <Form form={form} layout="vertical" initialValues={initialValues} onFinish={onSubmit}>
                <Form.Item name="type" label="Loại thông báo" rules={[{ required: true }]}><Input disabled /></Form.Item>
                <Form.Item name="subject" label="Tiêu đề email" rules={[{ required: true, message: 'Vui lòng nhập tiêu đề.' }]}><Input /></Form.Item>
                <Form.Item name="content" label="Nội dung email" rules={[{ required: true, message: 'Vui lòng nhập nội dung.' }]}><Input.TextArea rows={8} /></Form.Item>
                <Form.Item name="is_active" label="Đang kích hoạt" valuePropName="checked"><Switch /></Form.Item>
            </Form>
        </Modal>
    );
}

export default function NotificationTemplates() {
    const [loading, setLoading] = useState(true);
    const [templates, setTemplates] = useState([]);
    const [modalState, setModalState] = useState({ open: false, initialValues: null });

    const fetchTemplates = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/notifications');
            setTemplates(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được mẫu thông báo.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchTemplates();
    }, [fetchTemplates]);

    async function handleSubmit(values) {
        try {
            await api.post('/notifications', values);
            setModalState({ open: false, initialValues: null });
            message.success('Đã lưu mẫu thông báo.');
            fetchTemplates();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được mẫu thông báo.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Mẫu thông báo email"
                description="Quản lý nội dung email thông báo cho đơn hàng và tài khoản."
            />
            <Table
                rowKey="type"
                loading={loading}
                dataSource={templates}
                pagination={false}
                columns={[
                    { title: 'Loại', dataIndex: 'type' },
                    { title: 'Tiêu đề', dataIndex: 'subject' },
                    { title: 'Trạng thái', dataIndex: 'is_active', render: (value) => value ? 'Đang bật' : 'Đã tắt' },
                    {
                        title: 'Thao tác',
                        render: (_, item) => (
                            <Space>
                                <Button size="small" onClick={() => setModalState({ open: true, initialValues: item })}>Sửa</Button>
                            </Space>
                        ),
                    },
                ]}
            />
            <TemplateModal
                open={modalState.open}
                initialValues={modalState.initialValues}
                onCancel={() => setModalState({ open: false, initialValues: null })}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
