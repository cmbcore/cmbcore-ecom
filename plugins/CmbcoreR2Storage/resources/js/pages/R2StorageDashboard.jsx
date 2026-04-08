import React, { useCallback, useEffect, useState } from 'react';
import {
    Alert,
    Badge,
    Button,
    Card,
    Col,
    Divider,
    Form,
    Input,
    Row,
    Select,
    Space,
    Spin,
    Tag,
    Tooltip,
    Typography,
    message,
} from 'antd';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

const { Text, Title, Paragraph } = Typography;

const ALIAS = 'cmbcore-r2-storage';

// ─── StatusCard ──────────────────────────────────────────────────────────────

function StatusIndicator({ status, loading }) {
    if (loading) return <Spin size="small" />;
    if (!status) return null;

    return (
        <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                <Text type="secondary">Disk đang dùng:</Text>
                <Tag color={status.r2_active ? 'blue' : 'default'}>
                    {status.active_disk}
                </Tag>
            </div>
            {status.r2_active && status.bucket && (
                <div style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
                    <Text type="secondary">Bucket:</Text>
                    <Text strong>{status.bucket}</Text>
                </div>
            )}
            {!status.has_credentials && (
                <Tag color="orange">Chưa cấu hình đủ thông tin</Tag>
            )}
        </div>
    );
}

// ─── HowItWorks ──────────────────────────────────────────────────────────────

function HowItWorksCard() {
    return (
        <Card bordered={false} className="r2-info-card" style={{ background: 'linear-gradient(135deg, #f0f4ff 0%, #e8f1ff 100%)' }}>
            <Space direction="vertical" size={8} style={{ display: 'flex' }}>
                <Title level={5} style={{ margin: 0 }}>
                    <i className="fa-solid fa-circle-info" style={{ marginRight: 8, color: '#1677ff' }} aria-hidden="true" />
                    Cách hoạt động
                </Title>
                <Divider style={{ margin: '8px 0' }} />
                <div style={{ display: 'flex', flexDirection: 'column', gap: 6 }}>
                    {[
                        ['fa-solid fa-toggle-on', '#52c41a', 'Khi bật plugin & cấu hình đủ thông tin → mọi upload mới sẽ lưu vào Cloudflare R2'],
                        ['fa-solid fa-folder-open', '#1677ff', 'File cũ trên local storage vẫn hoạt động bình thường (không bị xóa)'],
                        ['fa-solid fa-toggle-off', '#ff7875', 'Khi tắt plugin → quay về local storage (public disk)'],
                        ['fa-solid fa-link', '#722ed1', 'URL R2 được trả về là CDN URL (public_url) + đường dẫn file'],
                    ].map(([icon, color, text]) => (
                        <div key={text} style={{ display: 'flex', gap: 8, alignItems: 'flex-start' }}>
                            <i className={icon} style={{ color, marginTop: 2, flexShrink: 0 }} aria-hidden="true" />
                            <Text style={{ fontSize: 13 }}>{text}</Text>
                        </div>
                    ))}
                </div>
            </Space>
        </Card>
    );
}

// ─── Main page ────────────────────────────────────────────────────────────────

export default function R2StorageDashboard() {
    const [form] = Form.useForm();
    const [configuration, setConfiguration] = useState(null);
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [statusLoading, setStatusLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [testing, setTesting] = useState(false);

    // ── Load config ──────────────────────────────────────────────────────
    const loadConfiguration = useCallback(async () => {
        setLoading(true);
        try {
            // Uses PluginManager generic GET /api/admin/plugins/{alias}/settings
            const res = await api.get(`/plugins/${ALIAS}/settings`);
            const data = res.data.data;
            setConfiguration(data);
            form.setFieldsValue(data.settings ?? {});
        } catch {
            message.error('Không thể tải cấu hình plugin.');
        } finally {
            setLoading(false);
        }
    }, [form]);

    // ── Load status ──────────────────────────────────────────────────────
    const loadStatus = useCallback(async () => {
        setStatusLoading(true);
        try {
            const res = await api.get(`/plugins/${ALIAS}/status`);
            setStatus(res.data.data);
        } catch {
            setStatus(null);
        } finally {
            setStatusLoading(false);
        }
    }, []);

    useEffect(() => {
        loadConfiguration();
        loadStatus();
    }, [loadConfiguration, loadStatus]);

    // ── Save settings ────────────────────────────────────────────────────
    const handleSubmit = async (values) => {
        setSubmitting(true);
        try {
            // Uses PluginManager generic PUT /api/admin/plugins/{alias}/settings
            const res = await api.put(`/plugins/${ALIAS}/settings`, values);
            const data = res.data.data;
            setConfiguration(data);
            form.setFieldsValue(data.settings ?? {});
            message.success('Đã lưu cấu hình R2 Storage.');
            await loadStatus();
        } catch (err) {
            message.error(err.response?.data?.message ?? 'Lưu cấu hình thất bại.');
        } finally {
            setSubmitting(false);
        }
    };

    // ── Test connection ──────────────────────────────────────────────────
    const handleTestConnection = async () => {
        setTesting(true);
        try {
            const res = await api.post(`/plugins/${ALIAS}/test-connection`);
            if (res.data.success) {
                message.success(res.data.message);
            } else {
                message.error(res.data.message);
            }
        } catch (err) {
            message.error(err.response?.data?.message ?? 'Kết nối thất bại.');
        } finally {
            setTesting(false);
        }
    };

    const isActive = configuration?.plugin?.is_active ?? false;

    return (
        <div className="r2-storage-page">
            <PageHeader
                title="CMBCore - R2 Storage"
                description="Cấu hình Cloudflare R2 làm storage backend cho media upload"
                extra={[
                    {
                        label: testing ? 'Đang kiểm tra...' : 'Kiểm tra kết nối',
                        icon: <i className="fa-solid fa-plug" aria-hidden="true" />,
                        onClick: handleTestConnection,
                        loading: testing,
                        disabled: !isActive,
                        type: 'default',
                    },
                ]}
            />

            <Spin spinning={loading}>
                <Row gutter={[24, 24]} align="start">
                    {/* ── Main form ── */}
                    <Col xs={24} lg={16}>
                        {/* Status bar */}
                        <Card bordered={false} style={{ marginBottom: 16 }}>
                            <Space direction="vertical" size={4} style={{ display: 'flex' }}>
                                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                                    <Badge
                                        status={isActive ? 'processing' : 'default'}
                                        text={
                                            <Text strong>
                                                Plugin: {isActive ? (
                                                    <span style={{ color: '#52c41a' }}>Đang hoạt động</span>
                                                ) : (
                                                    <span style={{ color: '#999' }}>Đã tắt</span>
                                                )}
                                            </Text>
                                        }
                                    />
                                </div>
                                <StatusIndicator status={status} loading={statusLoading} />
                                {isActive && !status?.r2_active && !statusLoading && (
                                    <Alert
                                        type="warning"
                                        showIcon
                                        message="Plugin đang bật nhưng chưa dùng R2. Vui lòng cấu hình đủ thông tin kết nối và lưu lại."
                                        style={{ marginTop: 8 }}
                                    />
                                )}
                                {isActive && status?.r2_active && (
                                    <Alert
                                        type="success"
                                        showIcon
                                        message={`R2 đang hoạt động${status.public_url ? ` — CDN: ${status.public_url}` : ''}`}
                                        style={{ marginTop: 8 }}
                                    />
                                )}
                            </Space>
                        </Card>

                        {/* Settings form */}
                        <Form form={form} layout="vertical" onFinish={handleSubmit}>
                            <Card
                                title={
                                    <span>
                                        <i className="fa-solid fa-key" style={{ marginRight: 8 }} aria-hidden="true" />
                                        Thông tin kết nối
                                    </span>
                                }
                                bordered={false}
                                style={{ marginBottom: 16 }}
                            >
                                <Row gutter={[16, 0]}>
                                    <Col xs={24} md={12}>
                                        <Form.Item
                                            label="Account ID"
                                            name="account_id"
                                            rules={[{ required: true, message: 'Vui lòng nhập Cloudflare Account ID.' }]}
                                            extra="Tìm trong Cloudflare Dashboard → R2 Object Storage"
                                        >
                                            <Input
                                                placeholder="abc123def456..."
                                                prefix={<i className="fa-solid fa-fingerprint" style={{ color: '#999' }} aria-hidden="true" />}
                                            />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Form.Item
                                            label="Bucket Name"
                                            name="bucket"
                                            rules={[{ required: true, message: 'Vui lòng nhập tên bucket.' }]}
                                        >
                                            <Input
                                                placeholder="my-media-bucket"
                                                prefix={<i className="fa-solid fa-bucket" style={{ color: '#999' }} aria-hidden="true" />}
                                            />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Form.Item
                                            label="Access Key ID"
                                            name="access_key_id"
                                            rules={[{ required: true, message: 'Vui lòng nhập Access Key ID.' }]}
                                        >
                                            <Input
                                                placeholder="R2-access-key-id"
                                                prefix={<i className="fa-solid fa-id-card" style={{ color: '#999' }} aria-hidden="true" />}
                                            />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Form.Item
                                            label="Secret Access Key"
                                            name="secret_access_key"
                                            rules={[{ required: true, message: 'Vui lòng nhập Secret Access Key.' }]}
                                        >
                                            <Input.Password
                                                placeholder="••••••••••••••••"
                                            />
                                        </Form.Item>
                                    </Col>
                                </Row>
                            </Card>

                            <Card
                                title={
                                    <span>
                                        <i className="fa-solid fa-globe" style={{ marginRight: 8 }} aria-hidden="true" />
                                        URL & Tùy chọn
                                    </span>
                                }
                                bordered={false}
                                style={{ marginBottom: 16 }}
                            >
                                <Row gutter={[16, 0]}>
                                    <Col xs={24} md={16}>
                                        <Form.Item
                                            label="Public URL (CDN / Custom Domain)"
                                            name="public_url"
                                            rules={[{ type: 'url', message: 'Định dạng URL không hợp lệ.' }]}
                                            extra="URL công khai để truy cập file. VD: https://pub-xxx.r2.dev hoặc https://cdn.domain.com"
                                        >
                                            <Input
                                                placeholder="https://pub-xxxxxxxx.r2.dev"
                                                prefix={<i className="fa-solid fa-link" style={{ color: '#999' }} aria-hidden="true" />}
                                            />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={8}>
                                        <Form.Item
                                            label="Region"
                                            name="region"
                                        >
                                            <Input placeholder="auto" />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Form.Item
                                            label="Thư mục upload mặc định"
                                            name="upload_folder"
                                            extra="Tiền tố thư mục trong bucket"
                                        >
                                            <Input
                                                placeholder="uploads"
                                                prefix={<i className="fa-solid fa-folder" style={{ color: '#999' }} aria-hidden="true" />}
                                            />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={12}>
                                        <Form.Item
                                            label="Visibility mặc định"
                                            name="visibility"
                                        >
                                            <Select
                                                options={[
                                                    { value: 'public', label: 'Public — truy cập công khai' },
                                                    { value: 'private', label: 'Private — cần signed URL' },
                                                ]}
                                            />
                                        </Form.Item>
                                    </Col>
                                </Row>
                            </Card>

                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={submitting}
                                icon={<i className="fa-solid fa-floppy-disk" aria-hidden="true" />}
                                size="large"
                                block
                            >
                                Lưu cấu hình R2
                            </Button>
                        </Form>
                    </Col>

                    {/* ── Sidebar ── */}
                    <Col xs={24} lg={8}>
                        <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                            <HowItWorksCard />

                            <Card
                                title={
                                    <span>
                                        <i className="fa-solid fa-circle-question" style={{ marginRight: 8, color: '#1677ff' }} aria-hidden="true" />
                                        Hướng dẫn nhanh
                                    </span>
                                }
                                bordered={false}
                                size="small"
                            >
                                <Space direction="vertical" size={4} style={{ display: 'flex' }}>
                                    {[
                                        '1. Tạo bucket trong Cloudflare R2 Dashboard',
                                        '2. Vào R2 → Manage R2 API Tokens → tạo token mới với quyền Object Read & Write',
                                        '3. Copy Account ID (từ trang chính R2), Access Key ID và Secret vào form',
                                        '4. Bật public access cho bucket hoặc dùng custom domain',
                                        '5. Điền Public URL (CDN URL), lưu và kiểm tra kết nối',
                                        '6. Bật plugin trong Plugin Manager',
                                    ].map((step) => (
                                        <Paragraph key={step} style={{ margin: 0, fontSize: 12, color: '#555' }}>
                                            {step}
                                        </Paragraph>
                                    ))}
                                </Space>
                            </Card>

                            <Card
                                title={
                                    <span>
                                        <i className="fa-solid fa-triangle-exclamation" style={{ marginRight: 8, color: '#faad14' }} aria-hidden="true" />
                                        Lưu ý
                                    </span>
                                }
                                bordered={false}
                                size="small"
                            >
                                <Space direction="vertical" size={4} style={{ display: 'flex' }}>
                                    <Text style={{ fontSize: 12, color: '#555' }}>
                                        • File cũ trên local storage sẽ <strong>không</strong> bị xóa hay di chuyển tự động.
                                    </Text>
                                    <Text style={{ fontSize: 12, color: '#555' }}>
                                        • Secret Access Key được lưu mã hóa trong database.
                                    </Text>
                                    <Text style={{ fontSize: 12, color: '#555' }}>
                                        • Cần cài package: <code>league/flysystem-aws-s3-v3</code>
                                    </Text>
                                </Space>
                            </Card>
                        </Space>
                    </Col>
                </Row>
            </Spin>
        </div>
    );
}
