import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Col, Form, Input, InputNumber, Modal, Popconfirm, Row, Select, Space, Switch, Table, Tabs, Tag, message } from 'antd';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';
import { deletePopconfirmProps } from '@admin/utils/confirm';

const METHOD_TYPES = [
    { label: 'Phí cố định', value: 'flat_rate' },
    { label: 'Miễn phí', value: 'free' },
    { label: 'Tính theo đơn', value: 'calculated' },
];

function ZoneModal({ open, onCancel, onSubmit, initialValues }) {
    const [form] = Form.useForm();

    return (
        <Modal
            open={open}
            title={initialValues?.id ? 'Sửa khu vực giao hàng' : 'Thêm khu vực giao hàng'}
            onCancel={onCancel}
            onOk={() => form.submit()}
            okText={initialValues?.id ? 'Lưu thay đổi' : 'Thêm mới'}
            cancelText="Hủy"
            destroyOnHidden
        >
            <Form form={form} layout="vertical" initialValues={initialValues} onFinish={onSubmit}>
                <Form.Item name="id" hidden><Input /></Form.Item>
                <Form.Item name="name" label="Tên khu vực" rules={[{ required: true, message: 'Vui lòng nhập tên khu vực.' }]}>
                    <Input />
                </Form.Item>
                <Form.Item name="provinces" label="Tỉnh/Thành phố (mỗi dòng một giá trị)">
                    <Input.TextArea rows={5} placeholder="Để trống = áp dụng toàn quốc" />
                </Form.Item>
                <Form.Item name="sort_order" label="Thứ tự sắp xếp">
                    <InputNumber min={0} style={{ width: '100%' }} />
                </Form.Item>
                <Form.Item name="is_active" label="Đang hoạt động" valuePropName="checked">
                    <Switch />
                </Form.Item>
            </Form>
        </Modal>
    );
}

function MethodModal({ open, onCancel, onSubmit, initialValues, zones }) {
    const [form] = Form.useForm();

    return (
        <Modal
            open={open}
            title={initialValues?.id ? 'Sửa phương thức vận chuyển' : 'Thêm phương thức vận chuyển'}
            onCancel={onCancel}
            onOk={() => form.submit()}
            okText={initialValues?.id ? 'Lưu thay đổi' : 'Thêm mới'}
            cancelText="Hủy"
            destroyOnHidden
            width={720}
        >
            <Form form={form} layout="vertical" initialValues={initialValues} onFinish={onSubmit}>
                <Form.Item name="id" hidden><Input /></Form.Item>
                <Row gutter={16}>
                    <Col span={12}>
                        <Form.Item name="name" label="Tên phương thức" rules={[{ required: true, message: 'Vui lòng nhập tên.' }]}>
                            <Input />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="code" label="Mã code (tùy chọn)">
                            <Input />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="shipping_zone_id" label="Khu vực áp dụng">
                            <Select
                                allowClear
                                placeholder="Để trống = toàn quốc"
                                options={zones.map((zone) => ({ label: zone.name, value: zone.id }))}
                            />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="type" label="Loại phí" rules={[{ required: true }]} initialValue="flat_rate">
                            <Select options={METHOD_TYPES} />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="fee" label="Phí vận chuyển (₫)">
                            <InputNumber min={0} style={{ width: '100%' }} />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="free_shipping_threshold" label="Mốc miễn phí ship (₫)">
                            <InputNumber min={0} style={{ width: '100%' }} />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="min_order_value" label="Đơn tối thiểu (₫)">
                            <InputNumber min={0} style={{ width: '100%' }} />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="max_order_value" label="Đơn tối đa (₫)">
                            <InputNumber min={0} style={{ width: '100%' }} />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="sort_order" label="Thứ tự sắp xếp">
                            <InputNumber min={0} style={{ width: '100%' }} />
                        </Form.Item>
                    </Col>
                    <Col span={12}>
                        <Form.Item name="is_active" label="Đang hoạt động" valuePropName="checked">
                            <Switch />
                        </Form.Item>
                    </Col>
                </Row>
            </Form>
        </Modal>
    );
}

export default function ShippingSettings() {
    const [loading, setLoading] = useState(true);
    const [payload, setPayload] = useState({ zones: [], methods: [] });
    const [zoneModal, setZoneModal] = useState({ open: false, initialValues: null });
    const [methodModal, setMethodModal] = useState({ open: false, initialValues: null });

    const fetchPayload = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/shipping');
            setPayload(response.data.data ?? { zones: [], methods: [] });
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được cấu hình vận chuyển.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchPayload();
    }, [fetchPayload]);

    async function submitZone(values) {
        try {
            await api.post('/shipping/zones', {
                ...values,
                provinces: typeof values.provinces === 'string' ? values.provinces : '',
            });
            setZoneModal({ open: false, initialValues: null });
            message.success('Đã lưu khu vực giao hàng.');
            fetchPayload();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được khu vực.');
        }
    }

    async function submitMethod(values) {
        try {
            await api.post('/shipping/methods', values);
            setMethodModal({ open: false, initialValues: null });
            message.success('Đã lưu phương thức vận chuyển.');
            fetchPayload();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được phương thức vận chuyển.');
        }
    }

    async function deleteZone(id) {
        try {
            await api.delete(`/shipping/zones/${id}`);
            message.success('Đã xóa khu vực.');
            fetchPayload();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xóa được khu vực.');
        }
    }

    async function deleteMethod(id) {
        try {
            await api.delete(`/shipping/methods/${id}`);
            message.success('Đã xóa phương thức vận chuyển.');
            fetchPayload();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xóa được phương thức vận chuyển.');
        }
    }

    return (
        <div>
            <PageHeader
                title="Vận chuyển"
                description="Quản lý vùng giao hàng và bảng phí ship."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: fetchPayload },
                    { label: 'Thêm khu vực', icon: <FontIcon name="create" />, onClick: () => setZoneModal({ open: true, initialValues: { is_active: true, sort_order: 0 } }) },
                    { label: 'Thêm phương thức', icon: <FontIcon name="create" />, type: 'primary', onClick: () => setMethodModal({ open: true, initialValues: { is_active: true, sort_order: 0, type: 'flat_rate', fee: 0 } }) },
                ]}
            />

            <Tabs
                items={[
                    {
                        key: 'zones',
                        label: 'Khu vực giao hàng',
                        children: (
                            <Card bordered={false}>
                                <Table
                                    rowKey="id"
                                    loading={loading}
                                    dataSource={payload.zones}
                                    columns={[
                                        { title: 'Tên khu vực', dataIndex: 'name' },
                                        {
                                            title: 'Tỉnh/Thành phố',
                                            dataIndex: 'provinces',
                                            render: (value) => (value ?? []).join(', ') || 'Toàn quốc',
                                        },
                                        {
                                            title: 'Trạng thái',
                                            dataIndex: 'is_active',
                                            render: (value) => <Tag color={value ? 'green' : 'red'}>{value ? 'Hoạt động' : 'Tắt'}</Tag>,
                                        },
                                        {
                                            title: 'Thao tác',
                                            render: (_, zone) => (
                                                <Space>
                                                    <Button size="small" onClick={() => setZoneModal({ open: true, initialValues: { ...zone, provinces: (zone.provinces ?? []).join('\n') } })}>Sửa</Button>
                                                    <Popconfirm {...deletePopconfirmProps(() => deleteZone(zone.id))}>
                                                        <Button size="small" danger>Xóa</Button>
                                                    </Popconfirm>
                                                </Space>
                                            ),
                                        },
                                    ]}
                                />
                            </Card>
                        ),
                    },
                    {
                        key: 'methods',
                        label: 'Phương thức vận chuyển',
                        children: (
                            <Card bordered={false}>
                                <Table
                                    rowKey="id"
                                    loading={loading}
                                    dataSource={payload.methods}
                                    columns={[
                                        { title: 'Tên', dataIndex: 'name' },
                                        { title: 'Mã code', dataIndex: 'code' },
                                        { title: 'Loại', dataIndex: 'type', render: (v) => METHOD_TYPES.find((t) => t.value === v)?.label ?? v },
                                        { title: 'Phí (₫)', dataIndex: 'fee', render: (v) => (v ?? 0).toLocaleString('vi-VN') },
                                        {
                                            title: 'Khu vực',
                                            render: (_, method) => method.zone?.name ?? 'Toàn quốc',
                                        },
                                        {
                                            title: 'Trạng thái',
                                            dataIndex: 'is_active',
                                            render: (value) => <Tag color={value ? 'green' : 'red'}>{value ? 'Hoạt động' : 'Tắt'}</Tag>,
                                        },
                                        {
                                            title: 'Thao tác',
                                            render: (_, method) => (
                                                <Space>
                                                    <Button size="small" onClick={() => setMethodModal({ open: true, initialValues: method })}>Sửa</Button>
                                                    <Popconfirm {...deletePopconfirmProps(() => deleteMethod(method.id))}>
                                                        <Button size="small" danger>Xóa</Button>
                                                    </Popconfirm>
                                                </Space>
                                            ),
                                        },
                                    ]}
                                />
                            </Card>
                        ),
                    },
                ]}
            />

            <ZoneModal
                open={zoneModal.open}
                initialValues={zoneModal.initialValues}
                onCancel={() => setZoneModal({ open: false, initialValues: null })}
                onSubmit={submitZone}
            />
            <MethodModal
                open={methodModal.open}
                zones={payload.zones}
                initialValues={methodModal.initialValues}
                onCancel={() => setMethodModal({ open: false, initialValues: null })}
                onSubmit={submitMethod}
            />
        </div>
    );
}
