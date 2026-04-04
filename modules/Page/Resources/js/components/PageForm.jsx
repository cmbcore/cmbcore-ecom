import React, { lazy, Suspense, useCallback, useState } from 'react';
import { Button, Card, Col, Form, Input, Row, Select, Space, Spin, Tag, Typography } from 'antd';
import RichTextEditor from '@admin/components/RichTextEditor';
import SEOFields from '@admin/components/SEOFields';
import SingleImageUploader from '@admin/components/SingleImageUploader';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import PageBlockBuilder from './PageBlockBuilder';

const PuckPageEditor = lazy(() => import('./PuckPageEditor'));

const { Text } = Typography;

export default function PageForm({
    form,
    initialValues,
    loading = false,
    onFinish,
    onCancel,
    submitLabel,
    templates = [],
    blocks = [],
    puckData = null,
    onPuckDataChange,
}) {
    const { t } = useLocale();
    const [showPuck, setShowPuck] = useState(false);
    const [localPuckData, setLocalPuckData] = useState(puckData);

    const isBuilderTemplate = Form.useWatch('template', form) === 'builder';

    // Called when Puck publishes
    const handlePuckPublish = useCallback((data) => {
        setLocalPuckData(data);
        setShowPuck(false);
        if (onPuckDataChange) {
            onPuckDataChange(data);
        }
    }, [onPuckDataChange]);

    // Wrap onFinish to inject puck_data
    const handleFinish = useCallback((values) => {
        if (isBuilderTemplate && localPuckData) {
            values.puck_data = localPuckData;
        }
        onFinish(values);
    }, [isBuilderTemplate, localPuckData, onFinish]);

    // Show Puck fullscreen editor
    if (showPuck) {
        // Extract plugin blocks from the blocks list (blocks with category or from API)
        const pluginBlocks = blocks.filter((b) => b.source === 'plugin');

        return (
            <Suspense fallback={
                <div style={{ display: 'flex', justifyContent: 'center', alignItems: 'center', height: '100vh' }}>
                    <Spin size="large" tip="Đang tải trình thiết kế..." />
                </div>
            }>
                <PuckPageEditor
                    puckData={localPuckData}
                    pluginBlocks={pluginBlocks}
                    pageTitle={form.getFieldValue('title') || ''}
                    onPublish={handlePuckPublish}
                    onClose={() => setShowPuck(false)}
                />
            </Suspense>
        );
    }

    const blockCount = localPuckData?.content?.length ?? 0;

    return (
        <Form
            form={form}
            layout="vertical"
            initialValues={initialValues}
            onFinish={handleFinish}
        >
            <Space direction="vertical" size={24} style={{ display: 'flex' }}>
                <Card
                    title={(
                        <span>
                            <FontIcon name="page" className="page-title__icon" />
                            {t('pages.sections.basic')}
                        </span>
                    )}
                    bordered={false}
                >
                    <Row gutter={16}>
                        <Col xs={24} lg={12}>
                            <Form.Item
                                label={t('pages.fields.title')}
                                name="title"
                                rules={[{ required: true, message: t('pages.validation.title_required') }]}
                            >
                                <Input placeholder={t('pages.placeholders.title')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('pages.fields.slug')} name="slug" extra={t('pages.help.slug')}>
                                <Input placeholder={t('pages.placeholders.slug')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={8}>
                            <Form.Item
                                label={t('pages.fields.template')}
                                name="template"
                                rules={[{ required: true, message: t('pages.validation.template_required') }]}
                            >
                                <Select
                                    options={templates.map((template) => ({
                                        label: template.label,
                                        value: template.name,
                                    }))}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={8}>
                            <Form.Item label={t('pages.fields.status')} name="status">
                                <Select
                                    options={[
                                        { label: t('pages.status_options.draft'), value: 'draft' },
                                        { label: t('pages.status_options.published'), value: 'published' },
                                        { label: t('pages.status_options.archived'), value: 'archived' },
                                    ]}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={8}>
                            <Form.Item label={t('pages.fields.published_at')} name="published_at" extra={t('pages.help.published_at')}>
                                <Input type="datetime-local" />
                            </Form.Item>
                        </Col>

                        <Col xs={24}>
                            <Form.Item label={t('pages.fields.featured_image')} name="featured_image_file">
                                <SingleImageUploader existingUrl={initialValues?.featured_image} size={1200} />
                            </Form.Item>
                        </Col>

                        <Col xs={24}>
                            <Form.Item label={t('pages.fields.excerpt')} name="excerpt">
                                <RichTextEditor minHeight={220} placeholder={t('pages.placeholders.excerpt')} />
                            </Form.Item>
                        </Col>
                    </Row>
                </Card>

                {/* Content section: changes based on template */}
                {isBuilderTemplate ? (
                    <Card
                        title={(
                            <span>
                                <FontIcon name="dashboard" className="page-title__icon" />
                                Trình thiết kế trang
                            </span>
                        )}
                        bordered={false}
                        extra={
                            blockCount > 0 && (
                                <Tag color="blue">{blockCount} block</Tag>
                            )
                        }
                    >
                        <div style={{ textAlign: 'center', padding: '32px 0' }}>
                            {blockCount > 0 ? (
                                <Space direction="vertical" size={12} align="center">
                                    <FontIcon name="check_circle" style={{ fontSize: 40, color: '#52c41a' }} />
                                    <Text>Đã thiết kế <strong>{blockCount} block</strong> nội dung.</Text>
                                    <Button
                                        type="primary"
                                        icon={<FontIcon name="edit" />}
                                        onClick={() => setShowPuck(true)}
                                        size="large"
                                    >
                                        Mở trình thiết kế
                                    </Button>
                                </Space>
                            ) : (
                                <Space direction="vertical" size={12} align="center">
                                    <FontIcon name="dashboard" style={{ fontSize: 40, color: '#bfbfbf' }} />
                                    <Text type="secondary">Sử dụng trình kéo thả để thiết kế nội dung trang.</Text>
                                    <Button
                                        type="primary"
                                        icon={<FontIcon name="add" />}
                                        onClick={() => setShowPuck(true)}
                                        size="large"
                                    >
                                        Bắt đầu thiết kế
                                    </Button>
                                </Space>
                            )}
                        </div>
                    </Card>
                ) : (
                    <>
                        <Card
                            title={(
                                <span>
                                    <FontIcon name="page" className="page-title__icon" />
                                    {t('pages.fields.content')}
                                </span>
                            )}
                            bordered={false}
                        >
                            <Form.Item name="content">
                                <RichTextEditor minHeight={460} placeholder={t('pages.placeholders.content')} />
                            </Form.Item>
                        </Card>

                        <Card
                            title={(
                                <span>
                                    <FontIcon name="appearance" className="page-title__icon" />
                                    {t('pages.sections.blocks')}
                                </span>
                            )}
                            bordered={false}
                        >
                            <PageBlockBuilder blocks={blocks} />
                        </Card>
                    </>
                )}

                <Card
                    title={(
                        <span>
                            <FontIcon name="seo" className="page-title__icon" />
                            {t('pages.sections.seo')}
                        </span>
                    )}
                    bordered={false}
                >
                    <SEOFields />
                </Card>

                <div className="product-form-card__actions">
                    <Button onClick={onCancel}>{t('common.cancel')}</Button>
                    <Button type="primary" htmlType="submit" loading={loading} icon={<FontIcon name="save" />}>
                        {submitLabel}
                    </Button>
                </div>
            </Space>
        </Form>
    );
}
