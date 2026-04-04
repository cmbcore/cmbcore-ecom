import React from 'react';
import { Tag } from 'antd';
import { useLocale } from '@admin/hooks/useLocale';

const STATUS_COLOR_MAP = {
    active: 'success',
    inactive: 'default',
    draft: 'default',
    archived: 'warning',
    published: 'processing',
    pending: 'default',
    confirmed: 'processing',
    processing: 'processing',
    shipping: 'blue',
    delivered: 'success',
    cancelled: 'error',
    unpaid: 'default',
    cod_pending: 'gold',
    paid: 'success',
};

export default function StatusBadge({ value }) {
    const { t } = useLocale();

    return (
        <Tag color={STATUS_COLOR_MAP[value] ?? 'default'}>
            {t(`common.status_labels.${value}`, {}, String(value ?? '').toUpperCase())}
        </Tag>
    );
}
