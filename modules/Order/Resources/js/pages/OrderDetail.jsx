import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Descriptions, Empty, Form, Input, List, Select, Space, Spin, Typography, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import StatusBadge from '@admin/components/ui/StatusBadge';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';

const { Paragraph, Text } = Typography;

function formatCurrency(value, locale) {
    return new Intl.NumberFormat(locale === 'vi' ? 'vi-VN' : 'en-US', {
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function formatDate(value, locale) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat(locale === 'vi' ? 'vi-VN' : 'en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function OrderDetail() {
    const navigate = useNavigate();
    const { id } = useParams();
    const { currentLocale, t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [order, setOrder] = useState(null);

    const fetchOrder = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get(`/orders/${id}`);
            const nextOrder = response.data.data ?? null;
            setOrder(nextOrder);
            form.setFieldsValue({
                order_status: nextOrder?.order_status,
                fulfillment_status: nextOrder?.fulfillment_status,
                payment_status: nextOrder?.payment_status,
                note: '',
            });
        } catch (error) {
            message.error(error.response?.data?.message ?? t('orders.messages.detail_failed'));
        } finally {
            setLoading(false);
        }
    }, [form, id, t]);

    useEffect(() => {
        fetchOrder();
    }, [fetchOrder]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const response = await api.put(`/orders/${id}`, values);
            const nextOrder = response.data.data ?? null;
            setOrder(nextOrder);
            form.setFieldsValue({
                order_status: nextOrder?.order_status,
                fulfillment_status: nextOrder?.fulfillment_status,
                payment_status: nextOrder?.payment_status,
                note: '',
            });
            message.success(t('orders.messages.updated'));
        } catch (error) {
            message.error(error.response?.data?.message ?? t('orders.messages.update_failed'));
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return <Spin size="large" />;
    }

    if (!order) {
        return <Empty description={t('orders.empty')} />;
    }

    return (
        <div>
            <PageHeader
                title={order.order_number}
                description={t('orders.detail_description')}
                extra={[
                    { label: t('orders.actions.back'), icon: <FontIcon name="arrow-left" />, onClick: () => navigate('/admin/orders') },
                    { label: t('orders.actions.reload'), icon: <FontIcon name="refresh" />, onClick: fetchOrder },
                ]}
            />

            <Space direction="vertical" size={24} style={{ width: '100%' }}>
                <Card bordered={false}>
                    <Descriptions column={{ xs: 1, md: 2, xl: 4 }} title={t('orders.sections.summary')}>
                        <Descriptions.Item label={t('orders.fields.order_number')}>{order.order_number}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.customer_name')}>{order.customer_name}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.customer_phone')}>{order.customer_phone}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.created_at')}>{formatDate(order.created_at, currentLocale)}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.order_status')}><StatusBadge value={order.order_status} /></Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.fulfillment_status')}><StatusBadge value={order.fulfillment_status} /></Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.payment_status')}><StatusBadge value={order.payment_status} /></Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.source')}>{order.source}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.shipping_recipient_name')}>{order.shipping_recipient_name}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.shipping_phone')}>{order.shipping_phone}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.shipping_full_address')} span={2}>{order.shipping_full_address}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.guest_email')}>{order.guest_email || order.user?.email || '-'}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.subtotal')}>{formatCurrency(order.subtotal, currentLocale)}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.shipping_total')}>{formatCurrency(order.shipping_total, currentLocale)}</Descriptions.Item>
                        <Descriptions.Item label={t('orders.fields.grand_total')}>{formatCurrency(order.grand_total, currentLocale)}</Descriptions.Item>
                    </Descriptions>

                    {order.note ? (
                        <Paragraph style={{ marginTop: 16, marginBottom: 0 }}>
                            <Text strong>{t('orders.fields.note')}:</Text> {order.note}
                        </Paragraph>
                    ) : null}
                </Card>

                <Card bordered={false} title={t('orders.sections.items')}>
                    {order.items?.length ? (
                        <List
                            dataSource={order.items}
                            renderItem={(item) => (
                                <List.Item>
                                    <List.Item.Meta
                                        title={`${item.product_name}${item.sku_name ? ` · ${item.sku_name}` : ''}`}
                                        description={
                                            <Space wrap>
                                                <Text>{t('orders.fields.quantity')}: {item.quantity}</Text>
                                                <Text>{t('orders.fields.unit_price')}: {formatCurrency(item.unit_price, currentLocale)}</Text>
                                                <Text>{t('orders.fields.line_total')}: {formatCurrency(item.line_total, currentLocale)}</Text>
                                            </Space>
                                        }
                                    />
                                </List.Item>
                            )}
                        />
                    ) : (
                        <Empty description={t('orders.empty_items')} />
                    )}
                </Card>

                <Card bordered={false} title={t('orders.sections.status_update')}>
                    <Form form={form} layout="vertical" onFinish={handleSubmit}>
                        <Space wrap align="start" size={16} style={{ width: '100%' }}>
                            <Form.Item name="order_status" label={t('orders.fields.order_status')} style={{ minWidth: 220 }}>
                                <Select
                                    options={[
                                        { label: t('common.status_labels.pending'), value: 'pending' },
                                        { label: t('common.status_labels.confirmed'), value: 'confirmed' },
                                        { label: t('common.status_labels.cancelled'), value: 'cancelled' },
                                    ]}
                                />
                            </Form.Item>
                            <Form.Item name="fulfillment_status" label={t('orders.fields.fulfillment_status')} style={{ minWidth: 220 }}>
                                <Select
                                    options={[
                                        { label: t('common.status_labels.pending'), value: 'pending' },
                                        { label: t('common.status_labels.processing'), value: 'processing' },
                                        { label: t('common.status_labels.shipping'), value: 'shipping' },
                                        { label: t('common.status_labels.delivered'), value: 'delivered' },
                                    ]}
                                />
                            </Form.Item>
                            <Form.Item name="payment_status" label={t('orders.fields.payment_status')} style={{ minWidth: 220 }}>
                                <Select
                                    options={[
                                        { label: t('common.status_labels.unpaid'), value: 'unpaid' },
                                        { label: t('common.status_labels.cod_pending'), value: 'cod_pending' },
                                        { label: t('common.status_labels.paid'), value: 'paid' },
                                    ]}
                                />
                            </Form.Item>
                        </Space>

                        <Form.Item name="note" label={t('orders.fields.note')}>
                            <Input.TextArea rows={3} />
                        </Form.Item>

                        <Button type="primary" htmlType="submit" loading={saving}>
                            {t('orders.actions.update')}
                        </Button>
                    </Form>
                </Card>

                <Card bordered={false} title={t('orders.sections.history')}>
                    {order.histories?.length ? (
                        <List
                            dataSource={order.histories}
                            renderItem={(history) => (
                                <List.Item>
                                    <List.Item.Meta
                                        title={
                                            <Space wrap>
                                                {history.from_status ? <StatusBadge value={history.from_status} /> : null}
                                                <Text>{history.from_status ? '->' : t('orders.messages.created_marker')}</Text>
                                                <StatusBadge value={history.to_status} />
                                            </Space>
                                        }
                                        description={
                                            <>
                                                <Paragraph style={{ marginBottom: 4 }}>
                                                    {history.note || t('orders.messages.no_history_note')}
                                                </Paragraph>
                                                <Text type="secondary">
                                                    {history.actor_name || t('orders.system_actor')} · {formatDate(history.created_at, currentLocale)}
                                                </Text>
                                            </>
                                        }
                                    />
                                </List.Item>
                            )}
                        />
                    ) : (
                        <Empty description={t('orders.empty_history')} />
                    )}
                </Card>
            </Space>
        </div>
    );
}
