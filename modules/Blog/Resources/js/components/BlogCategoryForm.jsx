import React from 'react';
import { Button, Card, Form, Input, Select } from 'antd';
import RichTextEditor from '@admin/components/RichTextEditor';
import SingleImageUploader from '@admin/components/SingleImageUploader';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';

export function normalizeBlogCategoryFormValues(category) {
    if (!category) {
        return {
            status: 'active',
        };
    }

    return {
        ...category,
        status: category.status ?? 'active',
    };
}

export default function BlogCategoryForm({
    form,
    initialValues,
    loading = false,
    onFinish,
    onCancel,
    submitLabel,
}) {
    const { t } = useLocale();

    return (
        <Card bordered={false}>
            <Form
                form={form}
                layout="vertical"
                initialValues={initialValues}
                onFinish={onFinish}
            >
                <Form.Item
                    label={t('blog_categories.fields.name')}
                    name="name"
                    rules={[{ required: true, message: t('blog_categories.validation.name_required') }]}
                >
                    <Input placeholder={t('blog_categories.placeholders.name')} />
                </Form.Item>

                <Form.Item label={t('blog_categories.fields.slug')} name="slug" extra={t('blog_categories.help.slug')}>
                    <Input placeholder={t('blog_categories.placeholders.slug')} />
                </Form.Item>

                <Form.Item label={t('blog_categories.fields.description')} name="description">
                    <RichTextEditor minHeight={260} placeholder={t('blog_categories.placeholders.description')} />
                </Form.Item>

                <Form.Item label={t('blog_categories.fields.image')} name="image_file">
                    <SingleImageUploader existingUrl={initialValues?.image} size={800} />
                </Form.Item>

                <Form.Item label={t('blog_categories.fields.status')} name="status">
                    <Select
                        options={[
                            { label: t('blog_categories.status_options.active'), value: 'active' },
                            { label: t('blog_categories.status_options.inactive'), value: 'inactive' },
                        ]}
                    />
                </Form.Item>

                <Form.Item label={t('blog_categories.fields.meta_title')} name="meta_title">
                    <Input placeholder={t('blog_categories.placeholders.meta_title')} />
                </Form.Item>

                <Form.Item label={t('blog_categories.fields.meta_description')} name="meta_description">
                    <Input.TextArea rows={3} placeholder={t('blog_categories.placeholders.meta_description')} />
                </Form.Item>

                <div className="product-form-card__actions">
                    <Button onClick={onCancel}>{t('common.cancel')}</Button>
                    <Button type="primary" htmlType="submit" loading={loading} icon={<FontIcon name="save" />}>
                        {submitLabel}
                    </Button>
                </div>
            </Form>
        </Card>
    );
}
