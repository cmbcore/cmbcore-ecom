import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Alert, Button, Card, Space, Tabs, Tag, Typography, message } from 'antd';
import {
    CheckCircleOutlined,
    CodeOutlined,
    GlobalOutlined,
    LinkOutlined,
    ReloadOutlined,
    RobotOutlined,
    SaveOutlined,
} from '@ant-design/icons';
import PageHeader from '@admin/components/ui/PageHeader';
import api from '@admin/services/api';

const { Text, Paragraph, Title } = Typography;

/* ── Robots.txt Editor ─────────────────────────────────────── */
function RobotsTab() {
    const [content, setContent] = useState('');
    const [original, setOriginal] = useState('');
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await api.get('/seo-tools');
            const val = res.data.data?.robots_content ?? '';
            setContent(val);
            setOriginal(val);
        } catch {
            message.error('Không tải được nội dung robots.txt.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { load(); }, [load]);

    const handleSave = async () => {
        setSaving(true);
        try {
            await api.put('/seo-tools/robots', { content });
            setOriginal(content);
            message.success('Đã lưu robots.txt thành công!');
        } catch (err) {
            message.error(err.response?.data?.message ?? 'Lưu thất bại.');
        } finally {
            setSaving(false);
        }
    };

    const isDirty = content !== original;

    return (
        <div style={{ display: 'grid', gap: 16 }}>
            <Alert
                type="info"
                showIcon
                message="Robots.txt kiểm soát crawler"
                description={
                    <span>
                        File này được đặt tại <code>public/robots.txt</code> và được đồng bộ tự động khi bạn lưu.
                        Thay đổi sẽ ảnh hưởng đến cách các bot tìm kiếm (Google, Bing…) crawl website.
                    </span>
                }
            />

            <div style={{ position: 'relative' }}>
                <textarea
                    value={content}
                    onChange={(e) => setContent(e.target.value)}
                    disabled={loading}
                    rows={16}
                    spellCheck={false}
                    style={{
                        width: '100%',
                        fontFamily: '"Fira Code", "Consolas", monospace',
                        fontSize: 13,
                        lineHeight: 1.6,
                        padding: '14px 16px',
                        border: '1px solid #d9d9d9',
                        borderRadius: 6,
                        background: '#1e1e2e',
                        color: '#cdd6f4',
                        resize: 'vertical',
                        outline: 'none',
                        boxSizing: 'border-box',
                    }}
                    onFocus={(e) => { e.target.style.borderColor = '#4096ff'; }}
                    onBlur={(e) => { e.target.style.borderColor = '#d9d9d9'; }}
                />
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                <Button
                    type="primary"
                    icon={<SaveOutlined />}
                    onClick={handleSave}
                    loading={saving}
                    disabled={!isDirty}
                    size="middle"
                >
                    Lưu robots.txt
                </Button>
                <Button icon={<ReloadOutlined />} onClick={load} disabled={loading} size="middle">
                    Tải lại
                </Button>
                {isDirty && (
                    <Tag color="orange">Có thay đổi chưa lưu</Tag>
                )}
                {!isDirty && original !== '' && (
                    <Tag color="green" icon={<CheckCircleOutlined />}>Đã lưu</Tag>
                )}
            </div>

            <Card size="small" title="Gợi ý mẫu phổ biến" bordered style={{ background: '#fafafa' }}>
                <Space direction="vertical" size={4} style={{ width: '100%' }}>
                    {[
                        {
                            label: 'Cho phép tất cả + Sitemap',
                            value: 'User-agent: *\nAllow: /\nSitemap: ' + window.location.origin + '/sitemap.xml',
                        },
                        {
                            label: 'Chặn tất cả bot (chế độ maintenance)',
                            value: 'User-agent: *\nDisallow: /',
                        },
                        {
                            label: 'Chặn thư mục admin',
                            value: 'User-agent: *\nAllow: /\nDisallow: /admin\nSitemap: ' + window.location.origin + '/sitemap.xml',
                        },
                    ].map(({ label, value }) => (
                        <Button
                            key={label}
                            type="link"
                            size="small"
                            style={{ padding: 0, height: 'auto', color: '#4096ff' }}
                            onClick={() => setContent(value)}
                        >
                            → {label}
                        </Button>
                    ))}
                </Space>
            </Card>
        </div>
    );
}

/* ── Custom Schema Editor ──────────────────────────────────── */
function SchemaTab() {
    const [json, setJson] = useState('');
    const [original, setOriginal] = useState('');
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [jsonError, setJsonError] = useState('');

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await api.get('/seo-tools/schema');
            const val = res.data.data?.schema_json ?? '';
            setJson(val);
            setOriginal(val);
            setJsonError('');
        } catch {
            message.error('Không tải được custom schema.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => { load(); }, [load]);

    const validateJson = (value) => {
        if (value.trim() === '') { setJsonError(''); return true; }
        try {
            JSON.parse(value);
            setJsonError('');
            return true;
        } catch (e) {
            setJsonError(e.message);
            return false;
        }
    };

    const handleChange = (e) => {
        const val = e.target.value;
        setJson(val);
        validateJson(val);
    };

    const handleFormat = () => {
        try {
            if (json.trim()) {
                setJson(JSON.stringify(JSON.parse(json), null, 2));
                setJsonError('');
            }
        } catch {}
    };

    const handleSave = async () => {
        if (!validateJson(json)) {
            message.error('Không thể lưu: JSON không hợp lệ.');
            return;
        }
        setSaving(true);
        try {
            await api.put('/seo-tools/schema', { schema_json: json });
            setOriginal(json);
            message.success('Đã lưu custom schema!');
        } catch (err) {
            message.error(err.response?.data?.message ?? 'Lưu thất bại.');
        } finally {
            setSaving(false);
        }
    };

    const isDirty = json !== original;

    const exampleLocalBusiness = JSON.stringify({
        '@type': 'LocalBusiness',
        '@id': window.location.origin + '#local-business',
        'name': 'Tên cửa hàng',
        'url': window.location.origin,
        'telephone': '+84-xxx-xxx-xxxx',
        'address': {
            '@type': 'PostalAddress',
            'streetAddress': 'Số nhà, tên đường',
            'addressLocality': 'Thành phố',
            'addressRegion': 'Tỉnh',
            'addressCountry': 'VN',
        },
    }, null, 2);

    return (
        <div style={{ display: 'grid', gap: 16 }}>
            <Alert
                type="info"
                showIcon
                icon={<CodeOutlined />}
                message="Custom Schema (JSON-LD)"
                description={
                    <span>
                        Schema bổ sung sẽ được merge vào <code>{'<script type="application/ld+json">'}</code> trên toàn site.
                        Bạn có thể nhập một object đơn hoặc một {'{"@graph": [...]}'}. Để trống nếu không cần.
                    </span>
                }
            />

            <div>
                <textarea
                    value={json}
                    onChange={handleChange}
                    disabled={loading}
                    rows={20}
                    spellCheck={false}
                    placeholder={'{\n  "@type": "LocalBusiness",\n  "name": "Tên cửa hàng"\n}'}
                    style={{
                        width: '100%',
                        fontFamily: '"Fira Code", "Consolas", monospace',
                        fontSize: 13,
                        lineHeight: 1.65,
                        padding: '14px 16px',
                        border: `1px solid ${jsonError ? '#ff4d4f' : '#d9d9d9'}`,
                        borderRadius: 6,
                        background: '#1e1e2e',
                        color: '#cdd6f4',
                        resize: 'vertical',
                        outline: 'none',
                        boxSizing: 'border-box',
                    }}
                    onFocus={(e) => { if (!jsonError) e.target.style.borderColor = '#4096ff'; }}
                    onBlur={(e) => { if (!jsonError) e.target.style.borderColor = '#d9d9d9'; }}
                />
                {jsonError && (
                    <div style={{ color: '#ff4d4f', fontSize: 12, marginTop: 6, fontFamily: 'monospace' }}>
                        ⚠ {jsonError}
                    </div>
                )}
            </div>

            <div style={{ display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                <Button
                    type="primary"
                    icon={<SaveOutlined />}
                    onClick={handleSave}
                    loading={saving}
                    disabled={!isDirty || !!jsonError}
                    size="middle"
                >
                    Lưu schema
                </Button>
                <Button onClick={handleFormat} disabled={loading || !!jsonError || !json.trim()} size="middle">
                    Format JSON
                </Button>
                <Button onClick={() => { setJson(''); setJsonError(''); }} disabled={loading} size="middle" danger>
                    Xóa
                </Button>
                <Button icon={<ReloadOutlined />} onClick={load} disabled={loading} size="middle">
                    Tải lại
                </Button>
                {isDirty && (
                    <Tag color="orange">Có thay đổi chưa lưu</Tag>
                )}
                {!isDirty && original !== '' && (
                    <Tag color="green" icon={<CheckCircleOutlined />}>Đã lưu</Tag>
                )}
            </div>

            <Card size="small" title={<><CodeOutlined /> Ví dụ LocalBusiness schema</>} bordered style={{ background: '#fafafa' }}>
                <Paragraph style={{ fontSize: 12 }}>
                    Thêm thông tin doanh nghiệp địa phương để hiển thị trên Google Maps và kết quả tìm kiếm rich:
                </Paragraph>
                <Button
                    type="link"
                    size="small"
                    style={{ padding: 0, height: 'auto' }}
                    onClick={() => { setJson(exampleLocalBusiness); validateJson(exampleLocalBusiness); }}
                >
                    → Dùng mẫu LocalBusiness
                </Button>
            </Card>
        </div>
    );
}

/* ── Sitemap Tab ────────────────────────────────────────────── */
function SitemapTab({ payload }) {
    return (
        <div style={{ display: 'grid', gap: 16 }}>
            <Alert
                type="success"
                showIcon
                icon={<GlobalOutlined />}
                message="Sitemap tự động"
                description="Sitemap được tạo động từ toàn bộ sản phẩm, danh mục, bài viết và trang tĩnh. Không cần cấu hình thêm."
            />
            <Card bordered={false}>
                <Space direction="vertical" size={12} style={{ width: '100%' }}>
                    <div>
                        <Text type="secondary" style={{ fontSize: 12, textTransform: 'uppercase', letterSpacing: 1 }}>URL Sitemap</Text>
                        <div style={{ marginTop: 6, display: 'flex', alignItems: 'center', gap: 10 }}>
                            <code style={{ background: '#f5f5f5', padding: '6px 12px', borderRadius: 4, fontSize: 13 }}>
                                {payload?.sitemap_url ?? '—'}
                            </code>
                            <Button
                                size="small"
                                icon={<LinkOutlined />}
                                href={payload?.sitemap_url}
                                target="_blank"
                                rel="noreferrer"
                            >
                                Mở
                            </Button>
                        </div>
                    </div>
                    <div>
                        <Text type="secondary" style={{ fontSize: 12, textTransform: 'uppercase', letterSpacing: 1 }}>
                            Đường dẫn robots.txt vật lý
                        </Text>
                        <div style={{ marginTop: 6 }}>
                            <code style={{ background: '#f5f5f5', padding: '6px 12px', borderRadius: 4, fontSize: 13 }}>
                                {payload?.robots_path ?? '—'}
                            </code>
                        </div>
                    </div>
                </Space>
            </Card>
        </div>
    );
}

/* ── Main Page ──────────────────────────────────────────────── */
export default function SeoToolsPage() {
    const [payload, setPayload] = useState(null);

    useEffect(() => {
        api.get('/seo-tools')
            .then((res) => setPayload(res.data.data ?? null))
            .catch(() => {});
    }, []);

    const items = [
        {
            key: 'robots',
            label: (
                <span><RobotOutlined style={{ marginRight: 6 }} />Robots.txt</span>
            ),
            children: <RobotsTab />,
        },
        {
            key: 'schema',
            label: (
                <span><CodeOutlined style={{ marginRight: 6 }} />Custom Schema</span>
            ),
            children: <SchemaTab />,
        },
        {
            key: 'sitemap',
            label: (
                <span><GlobalOutlined style={{ marginRight: 6 }} />Sitemap</span>
            ),
            children: <SitemapTab payload={payload} />,
        },
    ];

    return (
        <div>
            <PageHeader
                title="SEO Tools"
                description="Quản lý robots.txt, custom schema JSON-LD và sitemap cho storefront."
            />
            <Card bordered={false}>
                <Tabs defaultActiveKey="robots" items={items} size="middle" />
            </Card>
        </div>
    );
}
