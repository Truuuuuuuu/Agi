import '../css/app.css';
import './bootstrap';
import Alpine from 'alpinejs';

import { createVaultState } from './services/state';
import { initUserKeys } from './services/crypto';
import { encryptAndUploadFile } from './services/uploads';
import { decryptAndDownloadFile } from './services/downloads';
import {fetchFilesPage, deleteFileRequest} from './services/api';
import {toast, fmtBytes} from './services/ui';
import { toastManager } from './services/toast'

window.Alpine = Alpine;

Alpine.data('toastManager', toastManager)

window.vaultApp = function () {
    return {
        ...createVaultState(),

        async init() {
            await initUserKeys(this);
            await this.loadFiles();
        },

        async encryptAndUpload() {
            await encryptAndUploadFile(this);
        },

        async decryptAndDownload(file) {
            await decryptAndDownloadFile(this, file);
        },

        async loadPage(page) {
            this.loadingFiles = true;

            try {
                const data = await fetchFilesPage(page);

                this.files = data.data;
                this.currentPage = data.current_page;
                this.lastPage = data.last_page;
                this.totalFiles = data.total;

            } catch {
                this.toast('Failed to load files', 'error');
            } finally {
                this.loadingFiles = false;
            }
        },

        async loadFiles() {
            await this.loadPage(1);
        },

        confirmDelete(file) {
            this.deleteModal = { show: true, file };
        },

        async deleteFile() {
            if (!this.deleteModal.file) return;

            try {
                await deleteFileRequest(this.deleteModal.file.id);

                this.files = this.files.filter(
                    f => f.id !== this.deleteModal.file.id
                );

                window.toast('File deleted successfully', 'success')


            } catch (e) {
                this.toast('Delete failed: ' + e.message, 'error');
            } finally {
                this.deleteModal = { show: false, file: null };
            }
        },

        handleDrop(e) {
            this.isDragging = false;

            const f = e.dataTransfer.files[0];

            if (f) this.setPending(f);
        },

        handleFileSelect(e) {
            const f = e.target.files[0];

            if (f) this.setPending(f);
        },

        setPending(f) {
            this.pendingFile = f;
            this.dropLabel = `${f.name} — ${this.fmtBytes(f.size)}`;
        },

        clearPending() {
            this.pendingFile = null;
            this.dropLabel = 'Drop a file here or click to browse';

            if (this.$refs?.fileInput) {
                this.$refs.fileInput.value = '';
            }
        },

        toast,
        fmtBytes,
    };
};

Alpine.start();
