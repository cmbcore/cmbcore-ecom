import React, { useCallback, useEffect, useState } from 'react';
import { Button, Card, Col, Form, Input, InputNumber, Row, Spin, Switch, Tabs, message } from 'antd';
import MediaPathInput from '@admin/components/media/MediaPathInput';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

function SettingField({ field }) {
    if (field.type === 'boolean') {
        return <Switch />;
    }

    if (field.type === 'number') {
        return <InputNumber style={{ width: '100%' }} />;
    }

    if (field.type === 'media') {
        return <MediaPathInput modalTitle={`Chọn file cho ${field.label ?? 'trường'}`} />;
    }

    if (field.type === 'text' && String(field.value ?? '').includes('\n')) {
        return <Input.TextArea rows={4} />;
    }

    return <Input />;
}

function normalizeInitialValues(groups) {
    return groups.reduce((accumulator, group) => {
        accumulator[group.key] = (group.fields ?? []).reduce((fieldAccumulator, field) => {
            fieldAccumulator[field.key] = field.value;
            return fieldAccumulator;
        }, {});

        return accumulator;
    }, {});
}

export default function SystemSettings() {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [groups, setGroups] = useState([]);

    const fetchSettings = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/system/settings');
            const payload = response.data.data ?? [];
            setGroups(payload);
            form.setFieldsValue(normalizeInitialValues(payload));
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được cài đặt hệ thống.');
        } finally {
            setLoading(false);
        }
    }, [form]);

    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const response = await api.put('/system/settings', values);
            const payload = response.data.data ?? [];
            setGroups(payload);
            form.setFieldsValue(normalizeInitialValues(payload));
            message.success(response.data.message ?? 'Đã cập nhật cài đặt.');
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không lưu được cài đặt hệ thống.');
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return <Spin size="large" />;
    }

    return (
        <div>
            <PageHeader
                title="Cài đặt hệ thống"
                description="Quản lý thông tin cửa hàng, tiền tệ, email, SEO và các trang chính sách."
                extra={[
                    { label: 'Tải lại', icon: <FontIcon name="refresh" />, onClick: fetchSettings },
                ]}
            />

            <Form form={form} layout="vertical" onFinish={handleSubmit}>
                <Tabs
                    items={groups.map((group) => ({
                        key: group.key,
                        label: group.label,
                        children: (
                            <Card bordered={false}>
                                {group.description ? <p>{group.description}</p> : null}
                                <Row gutter={[24, 24]}>
                                    {(group.fields ?? []).map((field) => (
                                        <Col xs={24} md={12} key={`${group.key}.${field.key}`}>
                                            <Form.Item
                                                name={[group.key, field.key]}
                                                label={field.label}
                                                valuePropName={field.type === 'boolean' ? 'checked' : 'value'}
                                                extra={field.description}
                                            >
                                                <SettingField field={field} />
                                            </Form.Item>
                                        </Col>
                                    ))}
                                </Row>
                            </Card>
                        ),
                    }))}
                />

                <Button type="primary" htmlType="submit" loading={saving}>
                    Lưu cài đặt
                </Button>
            </Form>
        </div>
    );
}
