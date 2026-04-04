import React from 'react';
import { Button, Card, Col, Form, Input, Row, Select, Space, Switch } from 'antd';
import CategoryTreeSelect from '@admin/components/CategoryTreeSelect';
import MediaUploader from '@admin/components/MediaUploader';
import RichTextEditor from '@admin/components/RichTextEditor';
import SEOFields from '@admin/components/SEOFields';
import SkuVariantManager from '@admin/components/SkuVariantManager';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { buildSkuOptions } from './productFormUtils';

export default function ProductForm({
    form,
    initialValues,
    categories = [],
    loading = false,
    onFinish,
    onCancel,
    submitLabel,
}) {
    const { t } = useLocale();
    const productType = Form.useWatch('type', form) ?? initialValues?.type ?? 'simple';
    const skus = Form.useWatch('skus', form) ?? initialValues?.skus ?? [];
    const skuOptions = buildSkuOptions(skus);

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
                            <FontIcon name="product" className="page-title__icon" />
                            {t('products.sections.basic')}
                        </span>
                    )}
                    bordered={false}
                >
                    <Row gutter={[24, 24]}>
                        <Col xs={24} lg={12}>
                            <Form.Item
                                label={t('products.fields.name')}
                                name="name"
                                rules={[{ required: true, message: t('products.validation.name_required') }]}
                            >
                                <Input placeholder={t('products.placeholders.name')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('products.fields.slug')} name="slug" extra={t('products.help.slug')}>
                                <Input placeholder={t('products.placeholders.slug')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('products.fields.category_id')} name="category_id">
                                <CategoryTreeSelect
                                    categories={categories}
                                    placeholder={t('products.placeholders.category_id')}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={12}>
                            <Form.Item label={t('products.fields.brand')} name="brand">
                                <Input placeholder={t('products.placeholders.brand')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={6}>
                            <Form.Item label={t('products.fields.rating_value')} name="rating_value">
                                <Input type="number" min={0} max={5} step="0.1" placeholder={t('products.placeholders.rating_value')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={6}>
                            <Form.Item label={t('products.fields.review_count')} name="review_count">
                                <Input type="number" min={0} step="1" placeholder={t('products.placeholders.review_count')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={6}>
                            <Form.Item label={t('products.fields.sold_count')} name="sold_count">
                                <Input type="number" min={0} step="1" placeholder={t('products.placeholders.sold_count')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={6}>
                            <Form.Item label={t('products.fields.status')} name="status">
                                <Select
                                    options={[
                                        { label: t('products.status_options.draft'), value: 'draft' },
                                        { label: t('products.status_options.active'), value: 'active' },
                                        { label: t('products.status_options.archived'), value: 'archived' },
                                    ]}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24} lg={6}>
                            <Form.Item label={t('products.fields.type')} name="type">
                                <Select
                                    options={[
                                        { label: t('products.type_options.simple'), value: 'simple' },
                                        { label: t('products.type_options.variable'), value: 'variable' },
                                    ]}
                                />
                            </Form.Item>
                        </Col>

                        <Col xs={24}>
                            <Form.Item
                                label={t('products.fields.short_description')}
                                name="short_description"
                            >
                                <RichTextEditor minHeight={220} placeholder={t('products.placeholders.short_description')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24}>
                            <Form.Item
                                label={t('products.fields.description')}
                                name="description"
                            >
                                <RichTextEditor minHeight={380} placeholder={t('products.placeholders.description')} />
                            </Form.Item>
                        </Col>

                        <Col xs={24}>
                            <Form.Item
                                className="product-form-card__switch"
                                label={t('products.fields.is_featured')}
                                name="is_featured"
                                valuePropName="checked"
                                extra={t('products.help.is_featured')}
                            >
                                <Switch />
                            </Form.Item>
                        </Col>
                    </Row>
                </Card>

                <Card
                    className="product-form-card"
                    title={(
                        <span className="admin-card-title">
                            <FontIcon name="seo" className="page-title__icon" />
                            {t('products.sections.seo')}
                        </span>
                    )}
                    bordered={false}
                >
                    <SEOFields />
                </Card>

                <Card
                    className="product-form-card"
                    title={(
                        <span className="admin-card-title">
                            <FontIcon name="sku" className="page-title__icon" />
                            {t('products.sections.skus')}
                        </span>
                    )}
                    bordered={false}
                >
                    <SkuVariantManager form={form} productType={productType} />
                </Card>

                <Card
                    className="product-form-card"
                    title={(
                        <span className="admin-card-title">
                            <FontIcon name="media" className="page-title__icon" />
                            {t('products.sections.media')}
                        </span>
                    )}
                    bordered={false}
                >
                    <Form.Item name="media" extra={t('products.help.media')}>
                        <MediaUploader skuOptions={skuOptions} />
                    </Form.Item>
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
