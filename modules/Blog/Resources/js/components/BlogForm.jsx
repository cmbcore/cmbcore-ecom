import React from 'react';
import { Button, Card, Col, Form, Input, Row, Select, Space, Switch } from 'antd';
import RichTextEditor from '@admin/components/RichTextEditor';
import SEOFields from '@admin/components/SEOFields';
import SingleImageUploader from '@admin/components/SingleImageUploader';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';

export default function BlogForm({
    form,
    initialValues,
    categories = [],
    loading = false,
    onFinish,
    onCancel,
    submitLabel,
}) {
    const { t } = useLocale();

    return (
        <Form
            form={form}
            layout="vertical"
            initialValues={initialValues}
            onFinish={onFinish}
        >
            <Space direction="vertical" size={32} style={{ display: 'flex', width: '100%' }}>
                <Card
                    className="product-form-card"
                    title={(
                        <span className="admin-card-title">
                            <FontIcon name="blog" className="page-title__icon" />
                            {t('blogs.sections.basic')}
                        </span>
                    )}
                    bordered={false}
                >
                    <Row gutter={[24, 24]}>
                        <Col xs={24} lg={12}>
                            <Form.Item
                                label={t('blogs.fields.title')}
                                name="title"
                                rules={[{ required: true, message: t('blogs.validation.title_required') }]}
                            >
                                <Input placeholder={t('blogs.placeholders.title')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('blogs.fields.slug')} name="slug" extra={t('blogs.help.slug')}>
                                <Input placeholder={t('blogs.placeholders.slug')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('blogs.fields.blog_category_id')} name="blog_category_id">
                                <Select
                                    allowClear
                                    options={categories.map((category) => ({
                                        label: category.name,
                                        value: category.id,
                                    }))}
                                    placeholder={t('blogs.placeholders.blog_category_id')}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('blogs.fields.author_name')} name="author_name">
                                <Input placeholder={t('blogs.placeholders.author_name')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('blogs.fields.featured_image')} name="featured_image_file">
                                <SingleImageUploader existingUrl={initialValues?.featured_image} size={800} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={8}>
                            <Form.Item label={t('blogs.fields.status')} name="status">
                                <Select
                                    options={[
                                        { label: t('blogs.status_options.draft'), value: 'draft' },
                                        { label: t('blogs.status_options.published'), value: 'published' },
                                        { label: t('blogs.status_options.archived'), value: 'archived' },
                                    ]}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={8}>
                            <Form.Item label={t('blogs.fields.published_at')} name="published_at" extra={t('blogs.help.published_at')}>
                                <Input type="datetime-local" />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={8}>
                            <Form.Item
                                className="product-form-card__switch"
                                label={t('blogs.fields.is_featured')}
                                name="is_featured"
                                valuePropName="checked"
                            >
                                <Switch />
                            </Form.Item>
                        </Col>

                        <Col xs={24}>
                            <Form.Item label={t('blogs.fields.excerpt')} name="excerpt">
                                <RichTextEditor minHeight={220} placeholder={t('blogs.placeholders.excerpt')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24}>
                            <Form.Item label={t('blogs.fields.content')} name="content">
                                <RichTextEditor minHeight={420} placeholder={t('blogs.placeholders.content')} />
                            </Form.Item>
                        </Col>
                    </Row>
                </Card>

                <Card
                    className="product-form-card"
                    title={(
                        <span className="admin-card-title">
                            <FontIcon name="seo" className="page-title__icon" />
                            {t('blogs.sections.seo')}
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
