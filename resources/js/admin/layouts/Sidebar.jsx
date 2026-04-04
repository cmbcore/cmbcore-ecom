import React, { useMemo, useState } from 'react';
import { Menu } from 'antd';
import { useLocation } from 'react-router-dom';
import FontIcon from '@admin/components/ui/FontIcon';
import { useLocale } from '@admin/hooks/useLocale';
import { useModules } from '../hooks/useModules';

/**
 * Build an AntD Menu item from a module menu entry.
 * We use the item's `route` as the key so that
 * `selectedKeys={[location.pathname]}` works correctly.
 */
function mapMenuItem(item) {
    const children = Array.isArray(item.children) && item.children.length > 0
        ? item.children.map(mapMenuItem)
        : undefined;

    return {
        key: item.route,
        icon: <FontIcon name={item.icon} />,
        label: item.label,
        children,
    };
}

/**
 * Collect all keys of parent items (i.e. items that have `children`)
 * whose children include the currently active path.
 */
function findOpenKeys(items, pathname) {
    const openKeys = [];

    function walk(nodes) {
        for (const node of nodes) {
            if (Array.isArray(node.children) && node.children.length > 0) {
                const childMatch = node.children.some(
                    (child) => pathname === child.route || pathname.startsWith(child.route + '/'),
                );

                if (childMatch || walk(node.children, node.route)) {
                    openKeys.push(node.route);
                }
            }
        }

        return openKeys.length > 0;
    }

        walk(items);

    return openKeys;
}

export default function Sidebar({ onNavigate }) {
    const location = useLocation();
    const { menuItems } = useModules();
    const { t } = useLocale();

    // Compute which submenu parents should be open based on current path
    const defaultOpenKeys = useMemo(
        () => findOpenKeys(menuItems, location.pathname),
        // Only recalculate when menuItems load; keep open state local after that
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [menuItems],
    );

    const [openKeys, setOpenKeys] = useState(defaultOpenKeys);

    // Re-expand when menuItems first load (after initial render)
    const computedOpenKeys = useMemo(
        () => findOpenKeys(menuItems, location.pathname),
        [menuItems, location.pathname],
    );

    // Determine the selected key: prefer exact match, then prefix match within children
    const selectedKey = useMemo(() => {
        function findSelected(nodes) {
            for (const node of nodes) {
                if (Array.isArray(node.children) && node.children.length > 0) {
                    const found = findSelected(node.children);
                    if (found) return found;
                } else {
                    if (
                        location.pathname === node.route
                        || location.pathname.startsWith(node.route + '/')
                    ) {
                        return node.route;
                    }
                }
            }

            return null;
        }

        return findSelected(menuItems) ?? location.pathname;
    }, [menuItems, location.pathname]);

    return (
        <div className="admin-sidebar">
            <div className="admin-sidebar__brand">
                <span className="admin-sidebar__eyebrow">CMBCORE</span>
                <strong className="admin-sidebar__title">{t('layout.control_room')}</strong>
            </div>
            <Menu
                mode="inline"
                selectedKeys={[selectedKey]}
                openKeys={computedOpenKeys.length > 0 ? computedOpenKeys : openKeys}
                onOpenChange={setOpenKeys}
                items={menuItems.map(mapMenuItem)}
                onClick={({ key }) => onNavigate(key)}
                inlineIndent={20}
            />
        </div>
    );
}
