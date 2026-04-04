import React, { useEffect, useRef, useState } from 'react';
import { Form, message } from 'antd';
import { useNavigate, useParams } from 'react-router-dom';
import PageHeader from '@admin/components/ui/PageHeader';
import { useLocale } from '@admin/hooks/useLocale';
import api from '@admin/services/api';
import { toDateTimeLocalValue } from '@admin/utils/dateTime';
import PageForm from '../components/PageForm';
import { buildPagePayload } from '../components/pageBlocksPayload';

function normalizeFormValues(page) {
    return {
        ...page,
        content: page.content_body ?? page.content ?? '',
        published_at: toDateTimeLocalValue(page.published_at),
    };
}

export default function PageEdit() {
    const navigate = useNavigate();
    const { id } = useParams();
    const { t } = useLocale();
    const [form] = Form.useForm();
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [templates, setTemplates] = useState([]);
    const [blocks, setBlocks] = useState([]);
    const [pageRecord, setPageRecord] = useState(null);
    const [puckData, setPuckData] = useState(null);

    const tRef = useRef(t);
    tRef.current = t;

    useEffect(() => {
        let cancelled = false;
        setLoading(true);

        async function fetchPageData() {
            try {
                const [templatesResponse, pageResponse] = await Promise.all([
                    api.get('/pages/templates'),
                    api.get(`/pages/${id}`),
                ]);

                if (cancelled) return;

                setTemplates(templatesResponse.data.data?.templates ?? []);
                setBlocks(templatesResponse.data.data?.blocks ?? []);
                const nextPage = pageResponse.data.data;
                setPageRecord(nextPage);
                form.setFieldsValue(normalizeFormValues(nextPage));

                // Load puck_data if it exists
                if (nextPage.puck_data) {
                    setPuckData(
                        typeof nextPage.puck_data === 'string'
                            ? JSON.parse(nextPage.puck_data)
                            : nextPage.puck_data,
                    );
                }
            } catch (error) {
                if (cancelled) return;
                message.error(error.response?.data?.message ?? tRef.current('pages.messages.load_failed'));
                if (error.response?.status === 404) {
                    navigate('/admin/pages');
                }
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        fetchPageData();
        return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [id]);

    async function handleSubmit(values) {
        setSaving(true);

        try {
            const payload = buildPagePayload(values);
            const formData = new FormData();

            Object.entries(payload.values).forEach(([key, value]) => {
                if (value !== undefined) {
                    formData.append(key, typeof value === 'string' ? value : JSON.stringify(value));
                }
            });

            Object.entries(payload.directFiles ?? {}).forEach(([key, file]) => {
                formData.append(key, file);
            });

            payload.uploads.forEach(({ token, file }) => {
                formData.append(`uploads[${token}]`, file);
            });

            await api.post(`/pages/${id}?_method=PUT`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            message.success(t('pages.messages.updated'));
            navigate('/admin/pages');
        } catch (error) {
            message.error(error.response?.data?.message ?? t('pages.messages.update_failed'));
        } finally {
            setSaving(false);
        }
    }

    if (loading) {
        return null;
    }

    return (
        <div>
            <PageHeader
                title={t('pages.edit_title', { title: pageRecord?.title ?? t('pages.edit_fallback') })}
                description={t('pages.edit_description')}
            />
            <PageForm
                form={form}
                initialValues={normalizeFormValues(pageRecord ?? {})}
                loading={saving}
                onFinish={handleSubmit}
                onCancel={() => navigate('/admin/pages')}
                submitLabel={t('pages.actions.update')}
                templates={templates}
                blocks={blocks}
                puckData={puckData}
                onPuckDataChange={setPuckData}
            />
        </div>
    );
}
