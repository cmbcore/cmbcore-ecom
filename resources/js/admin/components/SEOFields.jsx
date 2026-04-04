import React from 'react';
import { Form, Input } from 'antd';
import { useLocale } from '@admin/hooks/useLocale';

const { TextArea } = Input;

function resolveName(namePrefix, field) {
    return Array.isArray(namePrefix) && namePrefix.length > 0
        ? [...namePrefix, field]
        : field;
}

export default function SEOFields({ namePrefix = [] }) {
    const { t } = useLocale();

    return (
        <>
            <Form.Item label={t('seo.fields.meta_title')} name={resolveName(namePrefix, 'meta_title')}>
                <Input maxLength={255} placeholder={t('seo.placeholders.meta_title')} showCount />
            </Form.Item>

            <Form.Item label={t('seo.fields.meta_description')} name={resolveName(namePrefix, 'meta_description')}>
                <TextArea rows={3} maxLength={320} placeholder={t('seo.placeholders.meta_description')} showCount />
            </Form.Item>

            <Form.Item label={t('seo.fields.meta_keywords')} name={resolveName(namePrefix, 'meta_keywords')}>
                <Input maxLength={255} placeholder={t('seo.placeholders.meta_keywords')} />
            </Form.Item>
        </>
    );
}
