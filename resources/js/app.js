import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.vaultApp = function () {
    return {
        // ── STATE ───────────────────────────────────────────────
        privateKey: null,
        publicKey: null,
        keyReady: false,

        files: [],
        loadingFiles: false,

        pendingFile: null,
        dropLabel: 'Drop a file here or click to browse',
        uploading: false,
        uploadPct: 0,
        uploadStep: '',
        isDragging: false,

        hashModal: { show: false, file: null },
        deleteModal: { show: false, file: null },
        toasts: [],

        currentPage: 1,
        lastPage: 1,
        totalFiles: 0,

        // ── LIFECYCLE ───────────────────────────────────────────
        async init() {
            await this.initUserKeys();
            await this.loadFiles();
        },

        // ────────────────────────────────────────────────────────
        // USER KEYPAIR (BREEZE COMPATIBLE)
        // ────────────────────────────────────────────────────────

        async initUserKeys() {
            const stored = localStorage.getItem('user_keypair');

            if (stored) {
                const parsed = JSON.parse(stored);

                this.privateKey = await crypto.subtle.importKey(
                    'jwk',
                    parsed.privateKey,
                    { name: 'RSA-OAEP', hash: 'SHA-256' },
                    true,
                    ['decrypt']
                );

                this.publicKey = await crypto.subtle.importKey(
                    'jwk',
                    parsed.publicKey,
                    { name: 'RSA-OAEP', hash: 'SHA-256' },
                    true,
                    ['encrypt']
                );

                this.keyReady = true;
                return;
            }

            const pair = await crypto.subtle.generateKey(
                {
                    name: 'RSA-OAEP',
                    modulusLength: 2048,
                    publicExponent: new Uint8Array([1, 0, 1]),
                    hash: 'SHA-256'
                },
                true,
                ['encrypt', 'decrypt']
            );

            const publicJwk = await crypto.subtle.exportKey('jwk', pair.publicKey);
            const privateJwk = await crypto.subtle.exportKey('jwk', pair.privateKey);

            localStorage.setItem(
                'user_keypair',
                JSON.stringify({
                    publicKey: publicJwk,
                    privateKey: privateJwk
                })
            );

            this.privateKey = pair.privateKey;
            this.publicKey = pair.publicKey;

            // send public key to backend (Breeze user)
            await fetch('/api/user/public-key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ public_key: publicJwk })
            });

            this.keyReady = true;
        },

        // ────────────────────────────────────────────────────────
        // CRYPTO HELPERS
        // ────────────────────────────────────────────────────────

        async sha256Hex(buffer) {
            const digest = await crypto.subtle.digest('SHA-256', buffer);

            return Array.from(new Uint8Array(digest))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        },

        // ────────────────────────────────────────────────────────
        // FILE SELECTION
        // ────────────────────────────────────────────────────────

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

        // ────────────────────────────────────────────────────────
        // UPLOAD (READY FOR PER-FILE KEY SYSTEM)
        // ────────────────────────────────────────────────────────

        async encryptAndUpload() {
            if (!this.keyReady || !this.pendingFile) return;

            this.uploading = true;
            this.uploadPct = 0;
            this.uploadStep = 'Reading file…';

            try {
                const plain = await this.pendingFile.arrayBuffer();

                this.uploadPct = 20;
                this.uploadStep = 'Generating AES key…';

                // 🔐 1. CREATE PER-FILE AES KEY
                const fileKey = await crypto.subtle.generateKey(
                    { name: 'AES-GCM', length: 256 },
                    true,
                    ['encrypt', 'decrypt']
                );

                const iv = crypto.getRandomValues(new Uint8Array(12));

                this.uploadPct = 40;
                this.uploadStep = 'Encrypting file…';

                // 🔐 2. ENCRYPT FILE WITH AES KEY
                const cipher = await crypto.subtle.encrypt(
                    { name: 'AES-GCM', iv },
                    fileKey,
                    plain
                );

                this.uploadPct = 60;
                this.uploadStep = 'Encrypting file key…';

                // 🔐 3. EXPORT AES KEY (JWK)
                const fileKeyJwk = await crypto.subtle.exportKey('jwk', fileKey);

                // 🔐 4. ENCRYPT AES KEY WITH USER PUBLIC KEY
                const encryptedKey = await crypto.subtle.encrypt(
                    { name: 'RSA-OAEP' },
                    this.publicKey,
                    new TextEncoder().encode(JSON.stringify(fileKeyJwk))
                );

                this.uploadPct = 75;
                this.uploadStep = 'Uploading…';

                // combine IV + cipher
                const blob = new Uint8Array(iv.byteLength + cipher.byteLength);
                blob.set(iv, 0);
                blob.set(new Uint8Array(cipher), iv.byteLength);

                const form = new FormData();
                form.append('file', new Blob([blob]), this.pendingFile.name + '.enc');
                form.append('encrypted_key', new Blob([encryptedKey]));
                form.append('original_name', this.pendingFile.name);
                form.append('original_size', String(this.pendingFile.size));

                const resp = await fetch('/api/files', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: form,
                });

                if (!resp.ok) throw new Error('Upload failed');

                const data = await resp.json();

                this.files.unshift(data.file);

                this.uploadPct = 100;
                this.toast('File uploaded securely 🔐', 'success');

                this.clearPending();

            } catch (e) {
                this.toast('Upload failed: ' + e.message, 'error');
            } finally {
                this.uploading = false;
                this.uploadPct = 0;
                this.uploadStep = '';
            }
        },

        // ────────────────────────────────────────────────────────
        // DOWNLOAD (READY FOR FILE KEY SYSTEM)
        // ────────────────────────────────────────────────────────

        async decryptAndDownload(file) {
            try {
                console.log('1. fetching metadata...');
                const resp = await fetch(`/api/files/${file.id}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!resp.ok) throw new Error('Download failed');
                // ADD THESE TWO LINES
                const text = await resp.text();
                console.log('2. raw response:', text);

                const data = JSON.parse(text); // replace resp.json() with this


                if (!data.encrypted_key) {
                    this.toast('Missing encrypted key in API response', 'error');
                    return;
                }

                let encryptedKey;
                try {
                    encryptedKey = Uint8Array.from(atob(data.encrypted_key), c => c.charCodeAt(0));
                    console.log('3. encryptedKey decoded, length:', encryptedKey.length);
                } catch (e) {
                    console.error('3. FAILED to decode encrypted key:', e);
                    this.toast('Failed to decode encrypted key', 'error');
                    return;
                }

                console.log('4. fetching file from:', data.file_url);
                const raw = await (await fetch(data.file_url)).arrayBuffer();
                console.log('5. raw file size:', raw.byteLength);

                const iv = raw.slice(0, 12);
                const cipher = raw.slice(12);

                console.log('6. privateKey:', this.privateKey);
                let decryptedKeyRaw;
                try {
                    decryptedKeyRaw = await crypto.subtle.decrypt(
                        { name: 'RSA-OAEP' },
                        this.privateKey,
                        encryptedKey
                    );
                    console.log('7. AES key decrypted');
                } catch (e) {
                    console.error('7. FAILED to decrypt AES key:', e);
                    this.toast('Failed to decrypt AES key — possible key mismatch', 'error');
                    return;
                }

                let fileKeyJwk;
                try {
                    fileKeyJwk = JSON.parse(new TextDecoder().decode(decryptedKeyRaw));
                    console.log('8. JWK parsed:', fileKeyJwk);
                } catch (e) {
                    console.error('8. FAILED to parse JWK:', e);
                    this.toast('Key mismatch: private key does not match the one used during upload', 'error');
                    return;
                }

                let fileKey;
                try {
                    fileKey = await crypto.subtle.importKey(
                        'jwk', fileKeyJwk, { name: 'AES-GCM' }, false, ['decrypt']
                    );
                    console.log('9. AES key imported');
                } catch (e) {
                    console.error('9. FAILED to import AES key:', e);
                    this.toast('Failed to import AES key', 'error');
                    return;
                }

                let plain;
                try {
                    plain = await crypto.subtle.decrypt(
                        { name: 'AES-GCM', iv: new Uint8Array(iv) },
                        fileKey,
                        cipher
                    );
                    console.log('10. file decrypted, size:', plain.byteLength);
                } catch (e) {
                    console.error('10. FAILED to decrypt file:', e);
                    this.toast('Failed to decrypt file — data may be corrupted', 'error');
                    return;
                }

                const url = URL.createObjectURL(
                    new Blob([plain], { type: file.mime_type || 'application/octet-stream' })
                );
                const a = document.createElement('a');
                a.href = url;
                a.download = file.original_name;
                document.body.appendChild(a);
                setTimeout(() => {
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    console.log('11. download triggered');
                }, 100);

                this.toast('File decrypted successfully 🔓', 'success');

            } catch (e) {
                console.error('UNCAUGHT ERROR:', e);
                this.toast('Decrypt failed: ' + e.message, 'error');
            }
        },

        // ────────────────────────────────────────────────────────
        // DELETE
        // ────────────────────────────────────────────────────────

        confirmDelete(file) {
            this.deleteModal = { show: true, file };
        },

        async deleteFile() {
            if (!this.deleteModal.file) return;

            try {
                const resp = await fetch(`/api/files/${this.deleteModal.file.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                if (!resp.ok) throw new Error('Delete failed');

                this.files = this.files.filter(f => f.id !== this.deleteModal.file.id);
                this.toast('File deleted successfully', 'success');

            } catch (e) {
                this.toast('Delete failed: ' + e.message, 'error');
            } finally {
                this.deleteModal = { show: false, file: null };
            }
        },



        // ────────────────────────────────────────────────────────
        // FILE LIST
        // ────────────────────────────────────────────────────────


        async loadPage(page) {
            this.loadingFiles = true;
            try {
                const resp = await fetch(`/api/files?page=${page}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();

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



        // ────────────────────────────────────────────────────────
        // UI
        // ────────────────────────────────────────────────────────

        toast(msg, type = 'info') {
            const id = Date.now() + Math.random();

            this.toasts.push({ id, msg, type });

            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 4000);
        },

        fmtBytes(b) {
            if (b < 1024) return b + ' B';
            if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
            return (b / 1048576).toFixed(1) + ' MB';
        }
    };
};

Alpine.start();