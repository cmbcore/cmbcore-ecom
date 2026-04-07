import React, { useCallback, useEffect, useState } from 'react';
import {
    Alert,
    Button,
    Card,
    Col,
    Form,
    Input,
    InputNumber,
    Row,
    Space,
    Spin,
    Switch,
    Tabs,
    Typography,
    message,
} from 'antd';
import MediaPathInput from '@admin/components/media/MediaPathInput';
import PageHeader from '@admin/components/ui/PageHeader';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';

const { Text } = Typography;

// ── Field renderer ─────────────────────────────────────────────────────────

function SettingField({ field }) {
    if (field.type === 'boolean') {
        return <Switch />;
    }

    if (field.type === 'number') {
        return <InputNumber style={{ width: '100%' }} />;
    }

    if (field.type === 'media') {
        return (
            <MediaPathInput modalTitle={`Chọn file cho ${field.label ?? 'trường'}`} />
        );
    }

    if (field.type === 'password') {
        return <Input.Password autoComplete="new-password" />;
    }

    if (field.type === 'textarea') {
        return <Input.TextArea rows={5} />;
    }

    if (field.type === 'text' && String(field.value ?? '').includes('\n')) {
        return <Input.TextArea rows={4} />;
    }

    return <Input />;
}

// ── Helpers ────────────────────────────────────────────────────────────────

function normalizeInitialValues(groups) {
    return groups.reduce((acc, group) => {
        acc[group.key] = (group.fields ?? []).reduce((fa, field) => {
            fa[field.key] = field.value;
            return fa;
        }, {});
        return acc;
    }, {});
}

const ICON_MAP = {
    'fa-solid fa-store': '🏪',
    'fa-solid fa-coins': '💰',
    'fa-solid fa-envelope': '✉️',
    'fa-solid fa-magnifying-glass-chart': '📊',
    'fa-solid fa-bag-shopping': '🛒',
    'fa-solid fa-file-shield': '📄',
};

// ── Main page ──────────────────────────────────────────────────────────────

export default function SystemSettings() {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testingEmail, setTestingEmail] = useState(false);
    const [groups, setGroups] = useState([]);

    // ── Load settings ──────────────────────────────────────────────────
    const fetchSettings = useCallback(async () => {
        setLoading(true);
        try {
            const response = await api.get('/system/settings');
            const payload = response.data.data ?? [];
            setGroups(payload);
            form.setFieldsValue(normalizeInitialValues(payload));
        } catch (error) {
            message.error(
                error.response?.data?.message ?? 'Không tải được cài đặt hệ thống.',
            );
        } finally {
            setLoading(false);
        }
    }, [form]);

    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    // ── Save settings ──────────────────────────────────────────────────
    async function handleSubmit(values) {
        setSaving(true);
        try {
            const response = await api.put('/system/settings', values);
            const payload = response.data.data ?? [];
            setGroups(payload);
            form.setFieldsValue(normalizeInitialValues(payload));
            message.success(response.data.message ?? 'Đã cập nhật cài đặt.');
        } catch (error) {
            message.error(
                error.response?.data?.message ?? 'Không lưu được cài đặt hệ thống.',
            );
        } finally {
            setSaving(false);
        }
    }

    // ── Test email ─────────────────────────────────────────────────────
    async function handleTestEmail() {
        const emailGroup = form.getFieldValue('email');
        if (!emailGroup?.mail_from_address) {
            message.warning('Vui lòng nhập địa chỉ người gửi trước khi kiểm tra.');
            return;
        }
        setTestingEmail(true);
        try {
            await api.post('/system/settings/test-email', emailGroup);
            message.success('Email kiểm tra đã được gửi! Vui lòng kiểm tra hộp thư đến.');
        } catch (error) {
            message.error(
                error.response?.data?.message ?? 'Gửi email kiểm tra thất bại.',
            );
        } finally {
            setTestingEmail(false);
        }
    }

    if (loading) {
        return (
            <div style={{ display: 'flex', justifyContent: 'center', paddingTop: 80 }}>
                <Spin size="large" tip="Đang tải cài đặt..." />
            </div>
        );
    }

    return (
        <div>
            <PageHeader
                title="Cài đặt hệ thống"
                description="Quản lý thông tin cửa hàng, tiền tệ, email SMTP, SEO và các trang chính sách."
                extra={[
                    {
                        label: 'Tải lại',
                        icon: <FontIcon name="refresh" />,
                        onClick: fetchSettings,
                    },
                ]}
            />

            <Form form={form} layout="vertical" onFinish={handleSubmit}>
                <Tabs
                    type="card"
                    size="large"
                    items={groups.map((group) => ({
                        key: group.key,
                        label: (
                            <Space size={6}>
                                {ICON_MAP[group.icon] ?? null}
                                {group.label}
                            </Space>
                        ),
                        children: (
                            <GroupPanel
                                group={group}
                                isEmailGroup={group.key === 'email'}
                                onTestEmail={handleTestEmail}
                                testingEmail={testingEmail}
                            />
                        ),
                    }))}
                />

                <div style={{ marginTop: 24 }}>
                    <Button
                        type="primary"
                        htmlType="submit"
                        loading={saving}
                        size="large"
                        icon={<FontIcon name="check" />}
                    >
                        Lưu tất cả cài đặt
                    </Button>
                </div>
            </Form>
        </div>
    );
}

// ── Group panel ────────────────────────────────────────────────────────────

function GroupPanel({ group, isEmailGroup, onTestEmail, testingEmail }) {
    const fields = group.fields ?? [];
    const halfFields = fields.filter((f) => f.type !== 'textarea' && f.type !== 'text' || String(f.value ?? '').length < 120);
    const fullFields = fields.filter((f) => !halfFields.includes(f));

    return (
        <Card bordered={false} style={{ borderTopLeftRadius: 0 }}>
            {group.description ? (
                <Text type="secondary" style={{ display: 'block', marginBottom: 24 }}>
                    {group.description}
                </Text>
            ) : null}

            {isEmailGroup ? (
                <Alert
                    type="info"
                    showIcon
                    icon={<FontIcon name="fa-solid fa-circle-info" />}
                    message="Cấu hình SMTP"
                    description="Sau khi lưu, bấm Gửi email kiểm tra để xác nhận kết nối. Mật khẩu được lưu mã hoá trong database."
                    style={{ marginBottom: 24 }}
                    action={
                        <Button
                            size="small"
                            loading={testingEmail}
                            onClick={onTestEmail}
                            icon={<FontIcon name="fa-solid fa-paper-plane" />}
                        >
                            Gửi email kiểm tra
                        </Button>
                    }
                />
            ) : null}

            <Row gutter={[24, 0]}>
                {fields.map((field) => {
                    const isFullWidth =
                        field.type === 'textarea' ||
                        field.type === 'media' ||
                        (field.type === 'text' && String(field.value ?? '').includes('\n'));

                    return (
                        <Col
                            xs={24}
                            md={isFullWidth ? 24 : 12}
                            key={`${group.key}.${field.key}`}
                        >
                            <Form.Item
                                name={[group.key, field.key]}
                                label={field.label}
                                valuePropName={field.type === 'boolean' ? 'checked' : 'value'}
                                extra={
                                    field.description ? (
                                        <Text type="secondary" style={{ fontSize: 12 }}>
                                            {field.description}
                                        </Text>
                                    ) : null
                                }
                            >
                                <SettingField field={field} />
                            </Form.Item>
                        </Col>
                    );
                })}
            </Row>
        </Card>
    );
}
