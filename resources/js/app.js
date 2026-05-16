import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;



window.vaultApp = function() {
        return {
            // ── State ──────────────────────────────────────────────────────────────
            cryptoKey:      null,
            keyFingerprint: '',
            keyReady:       false,

            files:          [],
            loadingFiles:   false,

            pendingFile:    null,
            dropLabel:      'Drop a file here or click to browse',
            uploading:      false,
            uploadPct:      0,
            uploadStep:     '',
            isDragging:     false,

            hashModal:      { show: false, file: null },
            deleteModal:    { show: false, file: null },
            toasts:         [],

            // ── Lifecycle ──────────────────────────────────────────────────────────
            async init() {
                await this.loadOrCreateKey();
                await this.loadFiles();
            },

            // ────────────────────────────────────────────────────────────────────────
            // KEY MANAGEMENT
            // ────────────────────────────────────────────────────────────────────────

            /**
             * Load the AES-256-GCM key from localStorage, or generate a new one.
             * The key is stored as a JWK so it survives page reloads.
             */
            async loadOrCreateKey() {
                const LS_KEY = `vault_key_{{ auth()->id() }}`;
                try {
                    const stored = localStorage.getItem(LS_KEY);
                    if (stored) {
                        const jwk = JSON.parse(stored);
                        this.cryptoKey = await crypto.subtle.importKey(
                            'jwk', jwk,
                            { name: 'AES-GCM', length: 256 },
                            true,
                            ['encrypt', 'decrypt']
                        );
                    } else {
                        this.cryptoKey = await crypto.subtle.generateKey(
                            { name: 'AES-GCM', length: 256 },
                            true,
                            ['encrypt', 'decrypt']
                        );
                        const jwk = await crypto.subtle.exportKey('jwk', this.cryptoKey);
                        localStorage.setItem(LS_KEY, JSON.stringify(jwk));
                    }

                    this.keyFingerprint = await this.fingerprintKey(this.cryptoKey);
                    this.keyReady = true;
                } catch (e) {
                    this.toast('Key error: ' + e.message, 'error');
                }
            },

            /**
             * Derive a short hex fingerprint from the raw key material for display.
             * This is NOT secret — it just helps identify which key is loaded.
             */
            async fingerprintKey(key) {
                const raw    = await crypto.subtle.exportKey('raw', key);
                const digest = await crypto.subtle.digest('SHA-256', raw);
                const hex    = Array.from(new Uint8Array(digest))
                    .map(b => b.toString(16).padStart(2, '0'))
                    .join('');
                return hex.slice(0, 8) + '…' + hex.slice(-8);
            },

            /** Download the JWK key as a JSON file for backup. */
            async exportKey() {
                try {
                    const jwk  = await crypto.subtle.exportKey('jwk', this.cryptoKey);
                    const blob = new Blob([JSON.stringify(jwk, null, 2)], { type: 'application/json' });
                    const url  = URL.createObjectURL(blob);
                    Object.assign(document.createElement('a'), {
                        href:     url,
                        download: `vault-key-{{ auth()->id() }}.json`,
                    }).click();
                    URL.revokeObjectURL(url);
                    this.toast('🔑 Key exported — store it safely.', 'info');
                } catch (e) {
                    this.toast('Export failed: ' + e.message, 'error');
                }
            },

            /** Import a previously exported JWK key file. */
            async importKey(event) {
                const file = event.target.files[0];
                if (!file) return;
                try {
                    const text = await file.text();
                    const jwk  = JSON.parse(text);
                    const key  = await crypto.subtle.importKey(
                        'jwk', jwk,
                        { name: 'AES-GCM', length: 256 },
                        true,
                        ['encrypt', 'decrypt']
                    );
                    const LS_KEY = `vault_key_{{ auth()->id() }}`;
                    localStorage.setItem(LS_KEY, JSON.stringify(jwk));
                    this.cryptoKey      = key;
                    this.keyFingerprint = await this.fingerprintKey(key);
                    this.toast('✅ Key imported successfully.', 'success');
                } catch (e) {
                    this.toast('Import failed — invalid key file.', 'error');
                }
            },

            // ────────────────────────────────────────────────────────────────────────
            // CRYPTO HELPERS
            // ────────────────────────────────────────────────────────────────────────

            /** SHA-256 of an ArrayBuffer → lowercase hex string. */
            async sha256Hex(buffer) {
                const digest = await crypto.subtle.digest('SHA-256', buffer);
                return Array.from(new Uint8Array(digest))
                    .map(b => b.toString(16).padStart(2, '0'))
                    .join('');
            },

            // ────────────────────────────────────────────────────────────────────────
            // FILE SELECTION
            // ────────────────────────────────────────────────────────────────────────

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
                this.dropLabel   = `${f.name} — ${this.fmtBytes(f.size)}`;
            },
            clearPending() {
                this.pendingFile          = null;
                this.dropLabel            = 'Drop a file here or click to browse';
                this.$refs.fileInput.value = '';
            },

            // ────────────────────────────────────────────────────────────────────────
            // ENCRYPT & UPLOAD
            // ────────────────────────────────────────────────────────────────────────

            async encryptAndUpload() {
                if (!this.keyReady || !this.pendingFile) return;

                this.uploading  = true;
                this.uploadPct  = 0;
                this.uploadStep = 'Reading file…';

                try {
                    // 1. Read plaintext
                    const plain = await this.pendingFile.arrayBuffer();
                    this.uploadPct  = 20;
                    this.uploadStep = 'Computing integrity hash…';

                    // 2. SHA-256 of plaintext — stored server-side for later verification
                    const hash = await this.sha256Hex(plain);
                    this.uploadPct  = 35;
                    this.uploadStep = 'Encrypting…';

                    // 3. Random 12-byte IV
                    const iv = crypto.getRandomValues(new Uint8Array(12));

                    // 4. AES-256-GCM encrypt
                    const cipher = await crypto.subtle.encrypt(
                        { name: 'AES-GCM', iv },
                        this.cryptoKey,
                        plain
                    );
                    this.uploadPct  = 60;
                    this.uploadStep = 'Uploading…';

                    // 5. Prepend IV to ciphertext → single blob [ IV | cipher ]
                    const blob = new Uint8Array(iv.byteLength + cipher.byteLength);
                    blob.set(iv, 0);
                    blob.set(new Uint8Array(cipher), iv.byteLength);

                    // 6. POST to Laravel
                    const form = new FormData();
                    form.append('file',          new Blob([blob]), this.pendingFile.name + '.enc');
                    form.append('iv', Array.from(iv).map(b => b.toString(16).padStart(2, '0')).join(''));
                    form.append('original_name', this.pendingFile.name);
                    form.append('original_size', String(this.pendingFile.size));
                    form.append('sha256_hash',   hash);

                    const resp = await fetch('/api/files', {
                        method:  'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept':       'application/json',
                        },
                        body: form,
                    });

                    this.uploadPct = 90;

                    if (!resp.ok) {
                        const err = await resp.json().catch(() => ({}));
                        throw new Error(err.message || `Upload failed (${resp.status})`);
                    }

                    const data = await resp.json();
                    this.files.unshift(data.file);
                    this.uploadPct  = 100;
                    this.uploadStep = '';
                    this.toast(`✅ "${this.pendingFile.name}" encrypted & uploaded.`, 'success');
                    this.clearPending();
                } catch (e) {
                    this.toast('Upload failed: ' + e.message, 'error');
                } finally {
                    this.uploading  = false;
                    this.uploadPct  = 0;
                    this.uploadStep = '';
                }
            },

            // ────────────────────────────────────────────────────────────────────────
            // DOWNLOAD & DECRYPT
            // ────────────────────────────────────────────────────────────────────────

            async decryptAndDownload(file) {
                if (!this.keyReady) return;

                // Reactive spinner on the row
                file.decrypting  = true;
                file.integrityOk = undefined;

                try {
                    // 1. Fetch encrypted blob
                    const resp = await fetch(`/api/files/${file.id}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    if (!resp.ok) throw new Error(`Download failed (${resp.status})`);

                    const raw = await resp.arrayBuffer();

                    // 2. Slice IV from front of blob
                    const iv     = raw.slice(0, 12);
                    const cipher = raw.slice(12);

                    // 3. AES-GCM decrypt — throws if key is wrong or data was tampered
                    let plain;
                    try {
                        plain = await crypto.subtle.decrypt(
                            { name: 'AES-GCM', iv: new Uint8Array(iv) },
                            this.cryptoKey,
                            cipher
                        );
                    } catch {
                        this.toast('❌ Decryption failed — this file was encrypted with a different key.', 'error');
                        file.integrityOk = false;
                        return;
                    }

                    // 4. Integrity — recompute SHA-256 and compare to stored hash
                    const computed = await this.sha256Hex(plain);
                    if (computed !== file.sha256_hash) {
                        this.toast('⚠️ Integrity check FAILED — file may be corrupted.', 'error');
                        file.integrityOk = false;
                        return;
                    }

                    file.integrityOk = true;

                    // 5. Trigger download
                    const blobOut = new Blob([plain], { type: file.mime_type || 'application/octet-stream' });
                    const url     = URL.createObjectURL(blobOut);
                    Object.assign(document.createElement('a'), {
                        href:     url,
                        download: file.original_name,
                    }).click();
                    URL.revokeObjectURL(url);

                    this.toast(`✅ "${file.original_name}" decrypted — integrity verified.`, 'success');
                } catch (e) {
                    this.toast('Error: ' + e.message, 'error');
                } finally {
                    file.decrypting = false;
                }
            },

            // ────────────────────────────────────────────────────────────────────────
            // FILE LIST & DELETE
            // ────────────────────────────────────────────────────────────────────────

            async loadFiles() {
                this.loadingFiles = true;
                try {
                    const resp = await fetch('/api/files', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await resp.json();
                    // Add reactive UI fields to each file record
                    this.files = data.map(f => ({ ...f, decrypting: false, integrityOk: undefined }));
                } catch {
                    this.toast('Failed to load files.', 'error');
                } finally {
                    this.loadingFiles = false;
                }
            },

            confirmDelete(file) {
                this.deleteModal = { show: true, file };
            },

            async deleteFile() {
                const file = this.deleteModal.file;
                this.deleteModal.show = false;
                try {
                    const resp = await fetch(`/api/files/${file.id}`, {
                        method:  'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept':       'application/json',
                        },
                    });
                    if (!resp.ok) throw new Error('Delete failed.');
                    this.files = this.files.filter(f => f.id !== file.id);
                    this.toast(`🗑 "${file.original_name}" deleted.`, 'success');
                } catch (e) {
                    this.toast(e.message, 'error');
                }
            },

            // ────────────────────────────────────────────────────────────────────────
            // UI HELPERS
            // ────────────────────────────────────────────────────────────────────────

            showHashModal(file) {
                this.hashModal = { show: true, file };
            },

            async copyToClipboard(text) {
                await navigator.clipboard.writeText(text);
                this.toast('📋 Copied to clipboard.', 'info');
            },

            toast(msg, type = 'info') {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, msg, type });
                setTimeout(() => {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }, 4500);
            },

            fmtBytes(b) {
                if (b < 1024)       return b + ' B';
                if (b < 1_048_576)  return (b / 1024).toFixed(1) + ' KB';
                return (b / 1_048_576).toFixed(1) + ' MB';
            },
        };
    }


    Alpine.start();