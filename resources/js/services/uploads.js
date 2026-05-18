import {
    generateFileKey,
    encryptFile
} from './crypto';

import {
    uploadEncryptedFile
} from './api';

export async function encryptAndUploadFile(ctx) {
    if (!ctx.keyReady || !ctx.pendingFile) return;

    ctx.uploading = true;
    ctx.uploadPct = 0;

    try {
        const plain = await ctx.pendingFile.arrayBuffer();

        ctx.uploadPct = 20;

        const fileKey = await generateFileKey();

        const iv = crypto.getRandomValues(
            new Uint8Array(12)
        );

        ctx.uploadPct = 40;

        const cipher = await encryptFile(
            fileKey,
            iv,
            plain
        );

        ctx.uploadPct = 60;

        const fileKeyJwk = await crypto.subtle.exportKey(
            'jwk',
            fileKey
        );

        const encryptedKey = await crypto.subtle.encrypt(
            {
                name: 'RSA-OAEP'
            },
            ctx.publicKey,
            new TextEncoder().encode(
                JSON.stringify(fileKeyJwk)
            )
        );

        ctx.uploadPct = 75;

        const blob = new Uint8Array(
            iv.byteLength + cipher.byteLength
        );

        blob.set(iv, 0);

        blob.set(
            new Uint8Array(cipher),
            iv.byteLength
        );

        const form = new FormData();

        form.append(
            'file',
            new Blob([blob]),
            ctx.pendingFile.name + '.enc'
        );

        form.append(
            'encrypted_key',
            new Blob([encryptedKey])
        );

        form.append(
            'original_name',
            ctx.pendingFile.name
        );

        form.append(
            'original_size',
            String(ctx.pendingFile.size)
        );

        const data = await uploadEncryptedFile(form);

        ctx.files.unshift(data.file);

        ctx.uploadPct = 100;

        ctx.toast(
            'File uploaded securely 🔐',
            'success'
        );

        ctx.clearPending();

    } catch (e) {
        ctx.toast(
            'Upload failed: ' + e.message,
            'error'
        );
    } finally {
        ctx.uploading = false;
        ctx.uploadPct = 0;
    }
}