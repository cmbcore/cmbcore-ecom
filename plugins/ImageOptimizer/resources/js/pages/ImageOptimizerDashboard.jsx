import React, { useCallback, useEffect, useRef, useState } from 'react';
import {
    Alert,
    Button,
    Card,
    Col,
    Divider,
    Form,
    Input,
    InputNumber,
    Radio,
    Row,
    Select,
    Slider,
    Space,
    Switch,
    Tabs,
    Tag,
    Typography,
    Upload,
    message,
} from 'antd';
import { InboxOutlined } from '@ant-design/icons';
import FontIcon from '@admin/components/ui/FontIcon';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

const { Text } = Typography;
const { Dragger } = Upload;

const POSITION_OPTIONS = [
    { label: 'Trên trái',    value: 'top-left' },
    { label: 'Trên giữa',   value: 'top-center' },
    { label: 'Trên phải',   value: 'top-right' },
    { label: 'Chính giữa',  value: 'center' },
    { label: 'Dưới trái',   value: 'bottom-left' },
    { label: 'Dưới giữa',  value: 'bottom-center' },
    { label: 'Dưới phải',   value: 'bottom-right' },
];

// ─── Canvas-based preview ─────────────────────────────────────────────────────

function CanvasPreview({ sourceFile, settings }) {
    const [previewUrl, setPreviewUrl] = useState(null);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef(null);

    const generatePreview = useCallback(async () => {
        if (!sourceFile) return;

        setLoading(true);
        try {
            const formData = new FormData();
            formData.append('image', sourceFile);
            // Pass current settings as JSON  
            formData.append('settings', JSON.stringify(settings));

            const res = await api.post('/plugins/image-optimizer/preview', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setPreviewUrl(res.data.data?.preview_url ?? null);
        } catch (e) {
            message.error(e.response?.data?.message ?? 'Không thể tạo preview.');
        } finally {
            setLoading(false);
        }
    }, [sourceFile, settings]);

    // Debounce preview generation when settings change
    useEffect(() => {
        if (!sourceFile) return;
        if (debounceRef.current) window.clearTimeout(debounceRef.current);
        debounceRef.current = window.setTimeout(generatePreview, 700);
        return () => window.clearTimeout(debounceRef.current);
    }, [sourceFile, settings, generatePreview]);

    if (!sourceFile) {
        return (
            <div className="io-preview__empty">
                <FontIcon name="image" />
                <Text type="secondary">Tải lên ảnh test để xem preview</Text>
            </div>
        );
    }

    return (
        <div className="io-preview__image-wrap">
            {loading && <div className="io-preview__loading"><div className="io-spinner" /></div>}
            {previewUrl && <img src={previewUrl} alt="Preview WebP" className="io-preview__img" />}
        </div>
    );
}

// ─── Main page ───────────────────────────────────────────────────────────────

export default function ImageOptimizerDashboard() {
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testFile, setTestFile] = useState(null);
    const [testFileObj, setTestFileObj] = useState(null);
    const [gdAvailable, setGdAvailable] = useState(true);
    const [webpAvailable, setWebpAvailable] = useState(true);

    // Live form values for preview sync
    const [liveSettings, setLiveSettings] = useState({});

    const wmType     = Form.useWatch('wm_type',    form) ?? 'text';
    const wmEnabled  = Form.useWatch('wm_enabled', form) ?? false;

    // ── Load settings from server ──
    const fetchSettings = useCallback(async () => {
        setLoading(true);
        try {
            const res = await api.get('/plugins/image-optimizer/settings');
            const data = res.data.data ?? {};
            setGdAvailable(data.gd_available ?? false);
            setWebpAvailable(data.webp_available ?? false);
            form.setFieldsValue(data.settings ?? {});
            setLiveSettings(data.settings ?? {});
        } catch (e) {
            message.error(e.response?.data?.message ?? 'Không tải được cài đặt.');
        } finally {
            setLoading(false);
        }
    }, [form]);

    useEffect(() => { fetchSettings(); }, [fetchSettings]);

    // ── Save settings ──
    async function handleSave(values) {
        setSaving(true);
        try {
            await api.put('/plugins/image-optimizer/settings', values);
            message.success('Đã lưu cài đặt.');
        } catch (e) {
            message.error(e.response?.data?.message ?? 'Lưu thất bại.');
        } finally {
            setSaving(false);
        }
    }

    function handleValuesChange(_, all) {
        setLiveSettings(all);
    }

    // ── Upload test image ──
    const uploadProps = {
        accept: 'image/jpeg,image/png,image/gif,image/webp',
        maxCount: 1,
        showUploadList: false,
        beforeUpload(file) {
            setTestFileObj(file);
            setTestFile(window.URL.createObjectURL(file));
            return false; // prevent auto upload
        },
    };

    const tabItems = [
        {
            key: 'conversion',
            label: <span><FontIcon name="compress" /> Nén & chuyển đổi</span>,
            children: (
                <Space direction="vertical" size={20} style={{ display: 'flex' }}>
                    <Form.Item name="enabled" label="Tự động tối ưu khi upload" valuePropName="checked">
                        <Switch />
                    </Form.Item>

                    <Row gutter={24}>
                        <Col xs={24} md={8}>
                            <Form.Item name="quality" label="Chất lượng WebP (%)">
                                <Slider min={10} max={100} marks={{ 10: '10', 50: '50', 82: '82*', 100: '100' }} />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={8}>
                            <Form.Item name="max_width" label="Chiều rộng tối đa (px)">
                                <InputNumber min={0} max={8000} style={{ width: '100%' }} addonAfter="px" />
                            </Form.Item>
                        </Col>
                        <Col xs={24} md={8}>
                            <Form.Item name="max_height" label="Chiều cao tối đa (px)">
                                <InputNumber min={0} max={8000} style={{ width: '100%' }} addonAfter="px" />
                            </Form.Item>
                        </Col>
                    </Row>

                    <Form.Item name="keep_original" label="Giữ bản gốc song song với WebP" valuePropName="checked">
                        <Switch />
                    </Form.Item>
                </Space>
            ),
        },
        {
            key: 'watermark',
            label: <span><FontIcon name="logo_watermark" /> Watermark</span>,
            children: (
                <Space direction="vertical" size={20} style={{ display: 'flex' }}>
                    <Form.Item name="wm_enabled" label="Bật watermark" valuePropName="checked">
                        <Switch />
                    </Form.Item>

                    {wmEnabled && (
                        <>
                            <Form.Item name="wm_type" label="Kiểu watermark">
                                <Radio.Group>
                                    <Radio.Button value="text">Chữ văn bản</Radio.Button>
                                    <Radio.Button value="image">Hình ảnh PNG trong suốt</Radio.Button>
                                </Radio.Group>
                            </Form.Item>

                            {wmType === 'text' ? (
                                <Row gutter={24}>
                                    <Col xs={24} md={12}>
                                        <Form.Item name="wm_text" label="Nội dung chữ">
                                            <Input placeholder="© CMBCore" />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={6}>
                                        <Form.Item name="wm_text_size" label="Cỡ chữ (px)">
                                            <InputNumber min={10} max={120} style={{ width: '100%' }} addonAfter="px" />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={6}>
                                        <Form.Item name="wm_text_color" label="Màu chữ">
                                            <Input placeholder="#ffffff" prefix={
                                                <span style={{
                                                    width: 14, height: 14, borderRadius: 3,
                                                    background: form.getFieldValue('wm_text_color') ?? '#ffffff',
                                                    border: '1px solid #ccc', display: 'inline-block'
                                                }} />
                                            } />
                                        </Form.Item>
                                    </Col>
                                </Row>
                            ) : (
                                <Row gutter={24}>
                                    <Col xs={24} md={14}>
                                        <Form.Item name="wm_image" label="Ảnh PNG trong suốt">
                                            <Input placeholder="/storage/watermarks/logo.png" />
                                        </Form.Item>
                                    </Col>
                                    <Col xs={24} md={10}>
                                        <Form.Item name="wm_image_width" label="Chiều rộng (px)">
                                            <InputNumber min={20} max={800} style={{ width: '100%' }} addonAfter="px" />
                                        </Form.Item>
                                    </Col>
                                </Row>
                            )}

                            <Row gutter={24}>
                                <Col xs={24} md={12}>
                                    <Form.Item name="wm_opacity" label="Độ mờ (%)">
                                        <Slider min={0} max={100} marks={{ 0: '0', 50: '50', 100: '100' }} />
                                    </Form.Item>
                                </Col>
                                <Col xs={24} md={12}>
                                    <Form.Item name="wm_padding" label="Cách mép (px)">
                                        <InputNumber min={0} max={200} style={{ width: '100%' }} addonAfter="px" />
                                    </Form.Item>
                                </Col>
                            </Row>

                            <Form.Item name="wm_position" label="Vị trí watermark">
                                <Select options={POSITION_OPTIONS} style={{ width: 200 }} />
                            </Form.Item>
                        </>
                    )}
                </Space>
            ),
        },
    ];

    return (
        <div className="io-page">
            <PageHeader
                title="Tối ưu hình ảnh"
                description="Tự động chuyển sang WebP, điều chỉnh kích thước và chèn watermark khi upload."
                extra={[
                    {
                        label: 'Tải lại',
                        icon: <FontIcon name="refresh" />,
                        onClick: fetchSettings,
                    },
                ]}
            />

            {/* GD / WebP status */}
            {(!gdAvailable || !webpAvailable) && (
                <Alert
                    type="error"
                    showIcon
                    style={{ marginBottom: 16 }}
                    message="Thiếu thư viện PHP"
                    description={`${!gdAvailable ? 'GD extension chưa bật. ' : ''}${!webpAvailable ? 'imagewebp() không khả dụng.' : ''} Plugin sẽ bỏ qua xử lý ảnh.`}
                />
            )}
            {gdAvailable && webpAvailable && (
                <Alert
                    type="success"
                    showIcon
                    style={{ marginBottom: 16 }}
                    message={<>GD + WebP sẵn sàng <Tag color="green">imagewebp()</Tag></>}
                />
            )}

            <Row gutter={[24, 24]} align="start">
                {/* ── Settings panel ── */}
                <Col xs={24} xl={14}>
                    <Form
                        form={form}
                        layout="vertical"
                        onFinish={handleSave}
                        onValuesChange={handleValuesChange}
                        initialValues={{
                            enabled: true,
                            quality: 82,
                            max_width: 1920,
                            max_height: 1920,
                            keep_original: false,
                            wm_enabled: false,
                            wm_type: 'text',
                            wm_text: '© CMBCore',
                            wm_text_size: 24,
                            wm_text_color: '#ffffff',
                            wm_opacity: 60,
                            wm_image_width: 200,
                            wm_position: 'bottom-right',
                            wm_padding: 16,
                        }}
                    >
                        <Card loading={loading} bordered={false} className="io-settings-card">
                            <Tabs items={tabItems} type="card" />
                        </Card>

                        <Card bordered={false} style={{ marginTop: 16 }}>
                            <Button
                                type="primary"
                                htmlType="submit"
                                loading={saving}
                                icon={<FontIcon name="save" />}
                                size="large"
                                block
                            >
                                Lưu cài đặt
                            </Button>
                        </Card>
                    </Form>
                </Col>

                {/* ── Live preview panel ── */}
                <Col xs={24} xl={10}>
                    <Card
                        bordered={false}
                        title={
                            <Space>
                                <FontIcon name="preview" />
                                <span>Preview WebP + Watermark</span>
                                <Tag color="blue">Live</Tag>
                            </Space>
                        }
                        className="io-preview-card"
                    >
                        <Space direction="vertical" size={16} style={{ display: 'flex' }}>
                            {/* Upload test image */}
                            <Dragger {...uploadProps} className="io-upload-dragger">
                                <p className="ant-upload-drag-icon">
                                    <InboxOutlined />
                                </p>
                                <p className="ant-upload-text">Kéo thả hoặc click để chọn ảnh test</p>
                                <p className="ant-upload-hint">JPG, PNG, GIF — tối đa 10MB</p>
                            </Dragger>

                            {/* Original image */}
                            {testFile && (
                                <div>
                                    <Text strong>Ảnh gốc:</Text>
                                    <div className="io-preview__original">
                                        <img src={testFile} alt="Ảnh gốc" className="io-preview__img" />
                                    </div>
                                </div>
                            )}

                            <Divider style={{ margin: '8px 0' }}>
                                <Text type="secondary" style={{ fontSize: 12 }}>↓ Sau khi xử lý (WebP)</Text>
                            </Divider>

                            {/* Processed preview */}
                            <div className="io-preview__box">
                                <CanvasPreview sourceFile={testFileObj} settings={liveSettings} />
                            </div>

                            {testFile && (
                                <Text type="secondary" style={{ fontSize: 12 }}>
                                    Preview cập nhật tự động khi thay đổi cài đặt (delay 0.7 giây)
                                </Text>
                            )}
                        </Space>
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
