import React, { useCallback, useEffect, useState } from 'react';
import { Button, Form, Input, InputNumber, Modal, Popconfirm, Select, Space, Switch, Table, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

function CouponModal({ open, initialValues, onCancel, onSubmit }) {
    const [form] = Form.useForm();

    return (
        <Modal
            open={open}
            title={initialValues?.id ? 'Sửa mã giảm giá' : 'Tạo mã giảm giá'}
            onCancel={onCancel}
            onOk={() => form.submit()}
            okText={initialValues?.id ? 'Lưu thay đổi' : 'Tạo mới'}
            cancelText="Hủy"
            destroyOnHidden
            width={720}
        >
            <Form form={form} layout="vertical" initialValues={initialValues} onFinish={onSubmit}>
                <Form.Item name="id" hidden><Input /></Form.Item>
                <Form.Item name="code" label="Mã coupon" rules={[{ required: true, message: 'Vui lòng nhập mã coupon.' }]}><Input placeholder="VD: SALE50" /></Form.Item>
                <Form.Item name="type" label="Loại giảm giá" rules={[{ required: true }]} initialValue="percentage">
                    <Select options={[
                        { label: 'Phần trăm (%)', value: 'percentage' },
                        { label: 'Số tiền cố định (₫)', value: 'fixed' },
                    ]} />
                </Form.Item>
                <Form.Item name="value" label="Giá trị" rules={[{ required: true, message: 'Vui lòng nhập giá trị.' }]}><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                <Form.Item name="min_order" label="Giá trị đơn tối thiểu (₫)"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                <Form.Item name="max_discount" label="Giảm tối đa (₫, dành cho phần trăm)"><InputNumber min={0} style={{ width: '100%' }} /></Form.Item>
                <Form.Item name="usage_limit" label="Tổng lượt sử dụng"><InputNumber min={1} style={{ width: '100%' }} placeholder="Để trống = không giới hạn" /></Form.Item>
                <Form.Item name="per_user_limit" label="Lượt dùng / mỗi khách"><InputNumber min={1} style={{ width: '100%' }} placeholder="Để trống = không giới hạn" /></Form.Item>
                <Form.Item name="description" label="Mô tả"><Input.TextArea rows={3} /></Form.Item>
                <Form.Item name="is_active" label="Đang kích hoạt" valuePropName="checked">
                    <Switch />
                </Form.Item>
            </Form>
        </Modal>
    );
}

export default function CouponList() {
    const [loading, setLoading] = useState(true);
    const [coupons, setCoupons] = useState([]);
    const [meta, setMeta] = useState({ current_page: 1, per_page: 20, total: 0 });
    const [modalState, setModalState] = useState({ open: false, initialValues: null });

    const fetchCoupons = useCallback(async (page = 1) => {
        setLoading(true);

        try {
            const response = await api.get('/coupons', { params: { page } });
            setCoupons(response.data.data ?? []);
            setMeta(response.data.meta ?? { current_page: 1, per_page: 20, total: 0 });
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được danh sách coupon.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchCoupons();
    }, [fetchCoupons]);

    async function handleSubmit(values) {
        try {
            await api.post('/coupons', values);
            setModalState({ open: false, initialValues: null });
            message.success('Đã lưu mã giảm giá.');
            fetchCoupons(meta.current_page);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được coupon.');
        }
    }

    async function handleDelete(id) {
        try {
            await api.delete(`/coupons/${id}`);
            message.success('Đã xóa mã giảm giá.');
            fetchCoupons(meta.current_page);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xóa được coupon.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Mã giảm giá"
                description="Quản lý mã giảm giá, giới hạn lượt dùng và điều kiện đơn hàng."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: () => fetchCoupons(meta.current_page) },
                    { label: 'Tạo mã giảm giá', icon: <FontIcon name="create" />, type: 'primary', onClick: () => setModalState({ open: true, initialValues: { type: 'percentage', value: 0, is_active: true } }) },
                ]}
            />

            <Table
                rowKey="id"
                loading={loading}
                dataSource={coupons}
                pagination={{
                    current: meta.current_page,
                    pageSize: meta.per_page,
                    total: meta.total,
                    onChange: (page) => fetchCoupons(page),
                }}
                columns={[
                    { title: 'Mã coupon', dataIndex: 'code' },
                    { title: 'Loại', dataIndex: 'type', render: (v) => v === 'percentage' ? 'Phần trăm' : 'Cố định' },
                    { title: 'Giá trị', dataIndex: 'value' },
                    { title: 'Đơn tối thiểu', dataIndex: 'min_order', render: (v) => v ? v.toLocaleString('vi-VN') + '₫' : '—' },
                    { title: 'Tổng lượt', dataIndex: 'usage_limit', render: (v) => v ?? 'Không giới hạn' },
                    {
                        title: 'Trạng thái',
                        dataIndex: 'is_active',
                        render: (value) => (value ? 'Đang hoạt động' : 'Đã tắt'),
                    },
                    {
                        title: 'Thao tác',
                        render: (_, coupon) => (
                            <Space>
                                <Button size="small" onClick={() => setModalState({ open: true, initialValues: coupon })}>Sửa</Button>
                                <Popconfirm {...deletePopconfirmProps(() => handleDelete(coupon.id))}>
                                    <Button size="small" danger>Xóa</Button>
                                </Popconfirm>
                            </Space>
                        ),
                    },
                ]}
            />

            <CouponModal
                open={modalState.open}
                initialValues={modalState.initialValues}
                onCancel={() => setModalState({ open: false, initialValues: null })}
                onSubmit={handleSubmit}
            />
        </div>
    );
}
