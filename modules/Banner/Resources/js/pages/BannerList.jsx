import React, { useCallback, useEffect, useState } from 'react';
import { Button, Form, Input, InputNumber, Modal, Popconfirm, Space, Switch, Table, message } from 'antd';
import MediaPathInput from '@admin/components/media/MediaPathInput';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

function BannerModal({ open, initialValues, onCancel, onSubmit }) {
    const [form] = Form.useForm();

    return (
        <Modal
            open={open}
            title={initialValues?.id ? 'Sửa banner' : 'Thêm banner'}
            onCancel={onCancel}
            onOk={() => form.submit()}
            okText={initialValues?.id ? 'Lưu thay đổi' : 'Thêm mới'}
            cancelText="Hủy"
            destroyOnHidden
            width={760}
        >
            <Form form={form} layout="vertical" initialValues={initialValues} onFinish={onSubmit}>
                <Form.Item name="id" hidden><Input /></Form.Item>
                <Form.Item name="title" label="Tiêu đề" rules={[{ required: true, message: 'Vui lòng nhập tiêu đề.' }]}><Input /></Form.Item>
                <Form.Item name="desktop_image" label="Ảnh Desktop" rules={[{ required: true, message: 'Vui lòng chọn ảnh desktop.' }]}>
                    <MediaPathInput modalTitle="Chọn ảnh desktop" />
                </Form.Item>
                <Form.Item name="mobile_image" label="Ảnh Mobile (tùy chọn)">
                    <MediaPathInput modalTitle="Chọn ảnh mobile" />
                </Form.Item>
                <Form.Item name="link" label="Đường dẫn (URL)"><Input placeholder="https://..." /></Form.Item>
                <Form.Item name="position" label="Vị trí hiển thị" rules={[{ required: true, message: 'Vui lòng nhập vị trí.' }]}><Input placeholder="VD: homepage_slider" /></Form.Item>
                <Form.Item name="sort_order" label="Thứ tự sắp xếp"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                <Form.Item name="is_active" label="Đang hiển thị" valuePropName="checked"><Switch /></Form.Item>
            </Form>
        </Modal>
    );
}

export default function BannerList() {
    const [loading, setLoading] = useState(true);
    const [items, setItems] = useState([]);
    const [modalState, setModalState] = useState({ open: false, initialValues: null });

    const fetchItems = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/banners');
            setItems(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được danh sách banner.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchItems();
    }, [fetchItems]);

    async function handleSubmit(values) {
        try {
            await api.post('/banners', values);
            setModalState({ open: false, initialValues: null });
            message.success('Đã lưu banner.');
            fetchItems();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được banner.');
        }
    }

    async function handleDelete(id) {
        try {
            await api.delete(`/banners/${id}`);
            message.success('Đã xóa banner.');
            fetchItems();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xóa được banner.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Banner"
                description="Quản lý slider và banner hiển thị trên giao diện cửa hàng."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: fetchItems },
                    {
                        label: 'Thêm banner',
                        icon: <FontIcon name="create" />,
                        type: 'primary',
                        onClick: () => setModalState({ open: true, initialValues: { position: 'homepage_slider', is_active: true, sort_order: 0 } }),
                    },
                ]}
            />
            <Table
                rowKey="id"
                loading={loading}
                dataSource={items}
                pagination={false}
                columns={[
                    { title: 'Tiêu đề', dataIndex: 'title' },
                    { title: 'Vị trí', dataIndex: 'position' },
                    { title: 'Ảnh Desktop', dataIndex: 'desktop_image', ellipsis: true },
                    {
                        title: 'Trạng thái',
                        dataIndex: 'is_active',
                        render: (value) => value ? 'Đang hiện' : 'Đã ẩn',
                    },
                    {
                        title: 'Thao tác',
                        render: (_, item) => (
                            <Space>
                                <Button size="small" onClick={() => setModalState({ open: true, initialValues: item })}>Sửa</Button>
                                <Popconfirm {...deletePopconfirmProps(() => handleDelete(item.id))}>
                                    <Button size="small" danger>Xóa</Button>
                                </Popconfirm>
                            </Space>
                        ),
                    },
                ]}
            />
            <BannerModal
                open={modalState.open}
                initialValues={modalState.initialValues}
                onCancel={() => setModalState({ open: false, initialValues: null })}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
