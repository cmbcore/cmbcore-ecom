import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Button, Image, Space, Tag, Typography, message } from 'antd';
import { ChonkyActions, FullFileBrowser } from 'chonky';
import FontIcon from '@admin/components/ui/FontIcon';
import api from '@admin/services/api';
import { showDeleteConfirm } from '@admin/utils/confirm';
import { dirnameFromPath, extFromPath, formatFileSize, normalizeFolderPath } from '@admin/utils/media';

const { Text } = Typography;
const ROOT_FOLDER_PATH = '';
const LOADING_PLACEHOLDERS = [null, null, null, null, null, null];

function folderId(path) {
    return `folder:${normalizeFolderPath(path)}`;
}

function fileId(id) {
    return `file:${id}`;
}

function folderName(path) {
    const normalized = normalizeFolderPath(path);

    if (!normalized) {
        return 'Media';
    }

    return normalized.split('/').pop() ?? normalized;
}

function compareEntries(left, right) {
    return String(left?.name ?? '').localeCompare(String(right?.name ?? ''), 'vi', {
        sensitivity: 'base',
        numeric: true,
    });
}

function ensureFolder(folders, folderChildren, path) {
    const folderPath = normalizeFolderPath(path);
    const existingFolder = folders.get(folderPath);

    if (existingFolder) {
        return existingFolder;
    }

    const record = {
        id: folderId(folderPath),
        name: folderName(folderPath),
        isDir: true,
        path: folderPath,
        folderPath,
        childrenCount: 0,
        color: '#0f766e',
        openable: true,
        draggable: false,
        droppable: false,
    };

    folders.set(folderPath, record);

    if (!folderChildren.has(folderPath)) {
        folderChildren.set(folderPath, new Set());
    }

    if (folderPath !== ROOT_FOLDER_PATH) {
        const parentPath = dirnameFromPath(folderPath);
        ensureFolder(folders, folderChildren, parentPath);
        folderChildren.get(parentPath)?.add(folderPath);
    }

    return record;
}

function buildMediaTree(items) {
    const folders = new Map();
    const folderChildren = new Map();
    const filesByFolder = new Map();
    const entriesById = new Map();

    ensureFolder(folders, folderChildren, ROOT_FOLDER_PATH);

    for (const item of items) {
        const folderPath = normalizeFolderPath(item.folder || dirnameFromPath(item.path));
        ensureFolder(folders, folderChildren, folderPath);

        if (!filesByFolder.has(folderPath)) {
            filesByFolder.set(folderPath, []);
        }

        const file = {
            id: fileId(item.id),
            name: item.filename || item.path,
            ext: extFromPath(item.filename || item.path),
            size: Number(item.size ?? 0),
            thumbnailUrl: item.mime_type?.startsWith('image/') ? item.url : undefined,
            path: item.path,
            url: item.url,
            mimeType: item.mime_type,
            mediaId: item.id,
            folderPath,
            openable: true,
            selectable: true,
            draggable: false,
        };

        filesByFolder.get(folderPath)?.push(file);
        entriesById.set(file.id, file);
    }

    for (const [path, folder] of folders.entries()) {
        folder.childrenCount = (folderChildren.get(path)?.size ?? 0) + (filesByFolder.get(path)?.length ?? 0);
        entriesById.set(folder.id, folder);
    }

    return {
        folders,
        folderChildren,
        filesByFolder,
        entriesById,
    };
}

function filesForFolder(tree, folderPath) {
    const childFolders = Array.from(tree.folderChildren.get(folderPath) ?? [])
        .map((path) => tree.folders.get(path))
        .filter(Boolean)
        .sort(compareEntries);

    const childFiles = [...(tree.filesByFolder.get(folderPath) ?? [])].sort(compareEntries);

    return [...childFolders, ...childFiles];
}

function folderChainFor(tree, currentFolderPath) {
    const chain = [];
    const segments = normalizeFolderPath(currentFolderPath).split('/').filter(Boolean);
    let currentPath = ROOT_FOLDER_PATH;
    const rootFolder = tree.folders.get(ROOT_FOLDER_PATH);

    if (rootFolder) {
        chain.push(rootFolder);
    }

    for (const segment of segments) {
        currentPath = currentPath ? `${currentPath}/${segment}` : segment;

        if (tree.folders.has(currentPath)) {
            chain.push(tree.folders.get(currentPath));
        }
    }

    return chain;
}

export default function MediaBrowser({
    picker = false,
    height = 560,
    onSelect,
    onSelectionChange,
}) {
    const fileInputRef = useRef(null);
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [currentFolderPath, setCurrentFolderPath] = useState(ROOT_FOLDER_PATH);
    const [selectedEntryId, setSelectedEntryId] = useState('');

    const tree = useMemo(() => buildMediaTree(items), [items]);
    const currentEntries = useMemo(
        () => filesForFolder(tree, currentFolderPath),
        [currentFolderPath, tree],
    );
    const folderChain = useMemo(
        () => folderChainFor(tree, currentFolderPath),
        [currentFolderPath, tree],
    );
    const selectedEntry = useMemo(
        () => tree.entriesById.get(selectedEntryId) ?? null,
        [selectedEntryId, tree],
    );
    const selectedFile = selectedEntry && !selectedEntry.isDir ? selectedEntry : null;

    const fetchItems = useCallback(async () => {
        setLoading(true);

        try {
            const response = await api.get('/media-library');
            setItems(response.data.data ?? []);
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải được danh sách media.');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchItems();
    }, [fetchItems]);

    useEffect(() => {
        if (currentFolderPath !== ROOT_FOLDER_PATH && !tree.folders.has(currentFolderPath)) {
            setCurrentFolderPath(ROOT_FOLDER_PATH);
        }
    }, [currentFolderPath, tree]);

    useEffect(() => {
        if (selectedEntryId && !tree.entriesById.has(selectedEntryId)) {
            setSelectedEntryId('');
        }
    }, [selectedEntryId, tree]);

    useEffect(() => {
        onSelectionChange?.(selectedFile);
    }, [onSelectionChange, selectedFile]);

    const openFolder = useCallback((folderPath) => {
        setCurrentFolderPath(normalizeFolderPath(folderPath));
        setSelectedEntryId('');
    }, []);

    const triggerUploadDialog = useCallback(() => {
        fileInputRef.current?.click();
    }, []);

    const handleUploadChange = useCallback(async (event) => {
        const files = Array.from(event.target.files ?? []);

        if (files.length === 0) {
            return;
        }

        setUploading(true);

        try {
            const targetFolder = currentFolderPath || 'uploads';

            await Promise.all(files.map((file) => {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('folder', targetFolder);

                return api.post('/media-library/upload', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });
            }));

            message.success(`Đã tải lên ${files.length} file.`);
            await fetchItems();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không tải lên được file.');
        } finally {
            setUploading(false);
            event.target.value = '';
        }
    }, [currentFolderPath, fetchItems]);

    const handleDeleteSelected = useCallback(async () => {
        if (!selectedFile) {
            return;
        }

        try {
            await api.delete(`/media-library/${selectedFile.mediaId}`);
            message.success('Đã xóa file.');
            setSelectedEntryId('');
            await fetchItems();
        } catch (error) {
            message.error(error.response?.data?.message ?? 'Không xóa được file.');
        }
    }, [fetchItems, selectedFile]);

    const emitSelect = useCallback((file) => {
        if (!file || file.isDir) {
            return;
        }

        onSelect?.(file);
    }, [onSelect]);

    const handleFileAction = useCallback((data) => {
        if (data.id === ChonkyActions.OpenFiles.id) {
            const target = data.payload.targetFile ?? data.payload.files?.[0];

            if (!target) {
                return;
            }

            setSelectedEntryId(target.id);

            if (target.isDir) {
                openFolder(target.folderPath ?? ROOT_FOLDER_PATH);
                return;
            }

            if (picker) {
                emitSelect(target);
                return;
            }

            if (target.url) {
                window.open(target.url, '_blank', 'noopener,noreferrer');
            }

            return;
        }

        if (
            data.id === ChonkyActions.MouseClickFile.id
            || data.id === ChonkyActions.KeyboardClickFile.id
        ) {
            setSelectedEntryId(data.payload.file.id);
            return;
        }

        if (data.id === ChonkyActions.OpenParentFolder.id) {
            openFolder(dirnameFromPath(currentFolderPath));
        }
    }, [currentFolderPath, emitSelect, openFolder, picker]);

    return (
        <div className={`media-browser${picker ? ' media-browser--picker' : ''}`}>
            <input
                ref={fileInputRef}
                type="file"
                hidden
                multiple
                onChange={handleUploadChange}
            />

            <div className="media-browser__toolbar">
                <Space wrap>
                    <Button icon={<FontIcon name="refresh" />} onClick={fetchItems} loading={loading}>
                        Tải lại
                    </Button>
                    <Button
                        type="primary"
                        icon={<FontIcon name="upload" />}
                        onClick={triggerUploadDialog}
                        loading={uploading}
                    >
                        Tải file lên
                    </Button>
                    {!picker ? (
                        <Button
                            danger
                            icon={<FontIcon name="delete" />}
                            onClick={() => showDeleteConfirm({
                                title: 'Xóa file media?',
                                content: selectedFile ? `File ${selectedFile.name} sẽ bị xóa khỏi thư viện media.` : undefined,
                                onConfirm: handleDeleteSelected,
                            })}
                            disabled={!selectedFile}
                        >
                            Xóa file
                        </Button>
                    ) : null}
                    {picker ? (
                        <Button
                            type="primary"
                            icon={<FontIcon name="media" />}
                            onClick={() => emitSelect(selectedFile)}
                            disabled={!selectedFile}
                        >
                            Chọn file
                        </Button>
                    ) : null}
                </Space>

                <div className="media-browser__meta">
                    <Tag>{currentFolderPath || 'Media'}</Tag>
                    <Text type="secondary">{items.length} mục</Text>
                </div>
            </div>

            <div className="media-browser__surface" style={{ height }}>
                <FullFileBrowser
                    files={loading ? LOADING_PLACEHOLDERS : currentEntries}
                    folderChain={folderChain}
                    onFileAction={handleFileAction}
                    disableDragAndDrop
                    disableDragAndDropProvider
                    clearSelectionOnOutsideClick={false}
                    defaultFileViewActionId={ChonkyActions.EnableGridView.id}
                />
            </div>

            <div className="media-browser__selection">
                {selectedFile ? (
                    <div className="media-browser__selection-card">
                        {selectedFile.thumbnailUrl ? (
                            <Image
                                src={selectedFile.thumbnailUrl}
                                alt={selectedFile.name}
                                width={72}
                                height={72}
                                preview={false}
                            />
                        ) : (
                            <div className="media-browser__selection-placeholder">
                                <FontIcon name="media" />
                            </div>
                        )}

                        <div className="media-browser__selection-body">
                            <Text strong>{selectedFile.name}</Text>
                            <Text type="secondary" className="media-browser__selection-path">
                                {selectedFile.path}
                            </Text>
                            <Space size={8} wrap>
                                {selectedFile.mimeType ? <Tag>{selectedFile.mimeType}</Tag> : null}
                                <Tag>{formatFileSize(selectedFile.size)}</Tag>
                            </Space>
                        </div>
                    </div>
                ) : (
                    <Text type="secondary">
                        {picker
                            ? 'Chọn một file để điền đường dẫn vào form.'
                            : 'Chọn file để xem nhanh, mở file hoặc xóa.'}
                    </Text>
                )}
            </div>
        </div>
    );
}
