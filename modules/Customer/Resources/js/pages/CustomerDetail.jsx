import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Descriptions, Empty, Form, Input, List, Modal, Space, Spin, Switch, Tag, Typography, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

const { Paragraph, Text } = Typography;

function formatDate(value, locale) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale === 'vi' ? 'vi-VN' : 'en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function CustomerDetail() {
    const navigate = useNavigate();
    const { id } = useParams();
    const { currentLocale, t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [customer, setCustomer] = useState(null);

    const fetchCustomer = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get(`/customers/${id}`);
            const nextCustomer = response.data.data ?? null;
            setCustomer(nextCustomer);
            form.setFieldsValue({
                name: nextCustomer?.name,
                email: nextCustomer?.email,
                phone: nextCustomer?.phone,
                password: '',
                is_active: nextCustomer?.is_active,
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('customers.messages.detail_failed'));
        } finally {
            setLoading(false);
        }
    }, [form, id, t]);

    useEffect(() => {
        fetchCustomer();
    }, [fetchCustomer]);

    async function handleSave(values) {
        setSaving(true);

        try {
            const response = await api.put(`/customers/${id}`, values);
            const nextCustomer = response.data.data ?? null;
            setCustomer(nextCustomer);
            form.setFieldsValue({
                name: nextCustomer?.name,
                email: nextCustomer?.email,
                phone: nextCustomer?.phone,
                password: '',
                is_active: nextCustomer?.is_active,
            });
            message.success(response.data.message ?? 'Đã cập nhật khách hàng.');
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không cập nhật được khách hàng.');
        } finally {
            setSaving(false);
        }
    }

    function handleDelete() {
        Modal.confirm({
            title: 'Xóa khách hàng?',
            content: 'Thao tác này sẽ xóa tài khoản khách hàng và các dữ liệu liên quan.',
            okText: 'Xóa',
            okButtonProps: { danger: true },
            cancelText: 'Hủy',
            onOk: async () => {
                try {
                    await api.delete(`/customers/${id}`);
                    message.success('Đã xóa khách hàng.');
                    navigate('/admin/customers');
                } catch (error) {
                    message.error(error.response?.data?.message ?? 'Không xóa được khách hàng.');
                }
            },
        });
    }

    if (loading) {
        return <Spin size="large" />;
    }

    if (!customer) {
        return <Empty description={t('customers.empty')} />;
    }

    return (
        <div>
            <PageHeader
                title={customer.name}
                description={t('customers.detail_description')}
                extra={[
                    { label: t('customers.actions.back'), icon: <FontIcon name="arrow-left" />, onClick: () => navigate('/admin/customers') },
                    { label: t('customers.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchCustomer },
                    { label: 'Xóa', danger: true, icon: <FontIcon name="trash" />, onClick: handleDelete },
                ]}
            />

            <Space direction="vertical" size={24} style={{ width: '100%' }}>
                <Card bordered={false}>
                    <Descriptions column={{ xs: 1, md: 2, xl: 4 }} title={t('customers.sections.profile')}>
                        <Descriptions.Item label={t('customers.fields.name')}>{customer.name}</Descriptions.Item>
                        <Descriptions.Item label={t('customers.fields.email')}>{customer.email || '-'}</Descriptions.Item>
                        <Descriptions.Item label={t('customers.fields.phone')}>{customer.phone || '-'}</Descriptions.Item>
                        <Descriptions.Item label={t('customers.fields.status')}>
                            <Tag color={customer.is_active ? 'success' : 'default'}>
                                {customer.is_active ? t('common.status_labels.active') : t('common.status_labels.inactive')}
                            </Tag>
                        </Descriptions.Item>
                        <Descriptions.Item label={t('customers.fields.address_count')}>{customer.address_count}</Descriptions.Item>
                        <Descriptions.Item label={t('customers.fields.order_count')}>{customer.order_count}</Descriptions.Item>
                        <Descriptions.Item label={t('customers.fields.created_at')}>
                            {formatDate(customer.created_at, currentLocale)}
                        </Descriptions.Item>
                    </Descriptions>
                </Card>

                <Card bordered={false} title="Cập nhật khách hàng">
                    <Form form={form} layout="vertical" onFinish={handleSave}>
                        <Form.Item name="name" label={t('customers.fields.name')} rules={[{ required: true, message: 'Vui lòng nhập họ tên.' }]}>
                            <Input />
                        </Form.Item>
                        <Form.Item name="email" label={t('customers.fields.email')} rules={[{ type: 'email', message: 'Email không hợp lệ.' }]}>
                            <Input />
                        </Form.Item>
                        <Form.Item name="phone" label={t('customers.fields.phone')}>
                            <Input />
                        </Form.Item>
                        <Form.Item name="password" label="Mật khẩu mới (tùy chọn)">
                            <Input.Password placeholder="Để trống nếu không đổi mật khẩu" />
                        </Form.Item>
                        <Form.Item name="is_active" label={t('customers.fields.status')} valuePropName="checked">
                            <Switch checkedChildren="Đang hoạt động" unCheckedChildren="Tạm dừng" />
                        </Form.Item>
                        <Space>
                            <Button type="primary" htmlType="submit" loading={saving}>
                                Lưu thay đổi
                            </Button>
                            <Button onClick={fetchCustomer}>Đặt lại</Button>
                        </Space>
                    </Form>
                </Card>

                <Card bordered={false} title={t('customers.sections.addresses')}>
                    {customer.addresses?.length ? (
                        <List
                            dataSource={customer.addresses}
                            renderItem={(address) => (
                                <List.Item>
                                    <List.Item.Meta
                                        title={
                                            <Space wrap>
                                                <Text strong>{address.label || t('customers.default_address')}</Text>
                                                {address.is_default ? <Tag color="blue">{t('customers.default_badge')}</Tag> : null}
                                            </Space>
                                        }
                                        description={
                                            <>
                                                <Paragraph style={{ marginBottom: 4 }}>
                                                    {address.recipient_name} · {address.phone}
                                                </Paragraph>
                                                <Paragraph type="secondary" style={{ marginBottom: 0 }}>
                                                    {address.full_address}
                                                </Paragraph>
                                            </>
                                        }
                                    />
                                </List.Item>
                            )}
                        />
                    ) : (
                        <Empty description={t('customers.empty_addresses')} />
                    )}
                </Card>

                <Card bordered={false} title={t('customers.sections.orders')}>
                    {customer.orders?.length ? (
                        <List
                            dataSource={customer.orders}
                            renderItem={(order) => (
                                <List.Item
                                    actions={[
                                        <Button key="view" size="small" onClick={() => navigate(`/admin/orders/${order.id}`)}>
                                            {t('customers.actions.view_order')}
                                        </Button>,
                                    ]}
                                >
                                    <List.Item.Meta
                                        title={order.order_number}
                                        description={
                                            <Space wrap>
                                                <StatusBadge value={order.order_status} />
                                                <StatusBadge value={order.fulfillment_status} />
                                                <Text>{order.grand_total}</Text>
                                                <Text type="secondary">{formatDate(order.created_at, currentLocale)}</Text>
                                            </Space>
                                        }
                                    />
                                </List.Item>
                            )}
                        />
                    ) : (
                        <Empty description={t('customers.empty_orders')} />
                    )}
                </Card>
            </Space>
        </div>
    );
}
