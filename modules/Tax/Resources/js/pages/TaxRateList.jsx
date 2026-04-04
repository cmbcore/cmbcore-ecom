import React, { useCallback, useEffect, useState } from 'react';
import { Button, Form, Input, InputNumber, Modal, Popconfirm, Space, Switch, Table, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

function TaxModal({ open, initialValues, onCancel, onSubmit }) {
    const [form] = Form.useForm();

    return (
        <Modal
            open={open}
            title={initialValues?.id ? 'Sửa thuế suất' : 'Thêm thuế suất'}
            onCancel={onCancel}
            onOk={() => form.submit()}
            okText={initialValues?.id ? 'Lưu thay đổi' : 'Thêm mới'}
            cancelText="Hủy"
            destroyOnHidden
        >
            <Form form={form} layout="vertical" initialValues={initialValues} onFinish={onSubmit}>
                <Form.Item name="id" hidden><Input /></Form.Item>
                <Form.Item name="name" label="Tên thuế suất" rules={[{ required: true, message: 'Vui lòng nhập tên.' }]}><Input /></Form.Item>
                <Form.Item name="province" label="Tỉnh/Thành phố"><Input placeholder="Để trống = áp dụng toàn quốc" /></Form.Item>
                <Form.Item name="rate" label="Tỷ lệ (%)" rules={[{ required: true, message: 'Vui lòng nhập tỷ lệ.' }]}><InputNumber min={0} max={100} style={{ width: '100%' }} /></Form.Item>
                <Form.Item name="threshold" label="Mốc áp dụng (giá trị đơn tối thiểu)"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                <Form.Item name="is_active" label="Đang áp dụng" valuePropName="checked"><Switch /></Form.Item>
            </Form>
        </Modal>
    );
}

export default function TaxRateList() {
    const [loading, setLoading] = useState(true);
    const [rates, setRates] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, per_page: 20, total: 0 });
    const [modalState, setModalState] = useState({ open: false, initialValues: null });

    const fetchRates = useCallback(async (page = 1) => {
        setLoading(true);

        try {
            const response = await api.get('/tax-rates', { params: { page } });
            setRates(response.data.data ?? []);
            setMeta(response.data.meta ?? { current_page: 1, per_page: 20, total: 0 });
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được bảng thuế.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchRates();
    }, [fetchRates]);

    async function handleSubmit(values) {
        try {
            await api.post('/tax-rates', values);
            setModalState({ open: false, initialValues: null });
            message.success('Đã lưu thuế suất.');
            fetchRates(meta.current_page);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được thuế suất.');
        }
    }

    async function handleDelete(id) {
        try {
            await api.delete(`/tax-rates/${id}`);
            message.success('Đã xóa thuế suất.');
            fetchRates(meta.current_page);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xóa được thuế suất.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Thuế"
                description="Quản lý thuế suất theo tỉnh/thành và mốc giá trị đơn hàng."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: () => fetchRates(meta.current_page) },
                    { label: 'Thêm thuế suất', icon: <FontIcon name="create" />, type: 'primary', onClick: () => setModalState({ open: true, initialValues: { is_active: true, rate: 0 } }) },
                ]}
            />

            <Table
                rowKey="id"
                loading={loading}
                dataSource={rates}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => fetchRates(page),
                }}
                columns={[
                    { title: 'Tên', dataIndex: 'name' },
                    { title: 'Tỉnh/Thành', dataIndex: 'province', render: (v) => v || 'Toàn quốc' },
                    { title: 'Tỷ lệ (%)', dataIndex: 'rate' },
                    { title: 'Mốc áp dụng', dataIndex: 'threshold', render: (v) => v ? v.toLocaleString('vi-VN') + '₫' : '—' },
                    {
                        title: 'Trạng thái',
                        dataIndex: 'is_active',
                        render: (value) => (value ? 'Đang áp dụng' : 'Tắt'),
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

            <TaxModal
                open={modalState.open}
                initialValues={modalState.initialValues}
                onCancel={() => setModalState({ open: false, initialValues: null })}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
