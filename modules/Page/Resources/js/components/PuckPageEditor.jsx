import React, { useCallback, useMemo, useRef } from 'react';
import { Puck } from '@puckeditor/core';
import '@puckeditor/core/puck.css';
import { buildPuckConfig, EMPTY_PUCK_DATA } from './puckConfig.jsx';

/**
 * Full-screen Puck page editor wrapper.
 *
 * @param {object} props
 * @param {object|null} props.puckData - Saved Puck JSON data or null for new pages
 * @param {Array} props.pluginBlocks - Extra block definitions from plugins
 * @param {string} props.pageTitle - Current page title for header display
 * @param {(data: object) => void} props.onPublish - Called when user clicks Publish
 * @param {() => void} props.onClose - Called when user wants to exit the editor
 */
export default function PuckPageEditor({
    puckData = null,
    pluginBlocks = [],
    pageTitle = '',
    onPublish,
    onClose,
}) {
    const hasClosedRef = useRef(false);

    const config = useMemo(() => buildPuckConfig(pluginBlocks), [pluginBlocks]);

    const initialData = useMemo(() => {
        if (puckData && puckData.content && Array.isArray(puckData.content)) {
            return puckData;
        }
        return EMPTY_PUCK_DATA;
    }, [puckData]);

    const handlePublish = useCallback(async (data) => {
        if (onPublish) {
            onPublish(data);
        }
    }, [onPublish]);

    // Override header to add a close button
    const overrides = useMemo(() => ({
        headerActions: ({ children }) => (
            <>
                {children}
                <button
                    type="button"
                    onClick={() => {
                        if (hasClosedRef.current) return;
                        hasClosedRef.current = true;
                        onClose?.();
                    }}
                    style={{
                        marginLeft: 8,
                        padding: '6px 16px',
                        border: '1px solid #d9d9d9',
                        borderRadius: 6,
                        background: '#fff',
                        cursor: 'pointer',
                        fontSize: 13,
                        color: '#333',
                    }}
                >
                    ← Quay lại
                </button>
            </>
        ),
    }), [onClose]);

    return (
        <div className="puck-editor-fullscreen">
            <Puck
                config={config}
                data={initialData}
                onPublish={handlePublish}
                headerTitle={pageTitle || 'Trình thiết kế trang'}
                overrides={overrides}
                viewports={[
                    { width: 1440, label: 'Desktop', icon: 'Monitor' },
                    { width: 768, label: 'Tablet', icon: 'Tablet' },
                    { width: 375, label: 'Mobile', icon: 'Smartphone' },
                ]}
            />
        </div>
    );
}
