import { Modal } from 'antd';

const DEFAULT_DELETE_TITLE = 'Xac nhan xoa?';
const DEFAULT_DELETE_DESCRIPTION = 'Hanh dong nay khong the hoan tac.';

export function deletePopconfirmProps(onConfirm, overrides = {}) {
    return {
        title: DEFAULT_DELETE_TITLE,
        description: DEFAULT_DELETE_DESCRIPTION,
        okText: 'Xoa',
        cancelText: 'Huy',
        okButtonProps: { danger: true },
        onConfirm,
        ...overrides,
    };
}

export function showDeleteConfirm({ onConfirm, ...overrides }) {
    Modal.confirm({
        title: DEFAULT_DELETE_TITLE,
        content: DEFAULT_DELETE_DESCRIPTION,
        okText: 'Xoa',
        cancelText: 'Huy',
        okButtonProps: { danger: true },
        onOk: onConfirm,
        ...overrides,
    });
}
