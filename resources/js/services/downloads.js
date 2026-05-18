import {
    fetchFileMetadata} from './api';

export async function decryptAndDownloadFile(ctx, file) {
    try {
        const data = await fetchFileMetadata(file.id);

        const encryptedKey = Uint8Array.from(
            atob(data.encrypted_key),
            c => c.charCodeAt(0)
        );

        const raw = await (
            await fetch(data.file_url)
        ).arrayBuffer();

        const iv = raw.slice(0, 12);
        const cipher = raw.slice(12);

        const decryptedKeyRaw =
            await crypto.subtle.decrypt(
                {
                    name: 'RSA-OAEP'
                },
                ctx.privateKey,
                encryptedKey
            );

        const fileKeyJwk = JSON.parse(
            new TextDecoder().decode(
                decryptedKeyRaw
            )
        );

        const fileKey =
            await crypto.subtle.importKey(
                'jwk',
                fileKeyJwk,
                {
                    name: 'AES-GCM'
                },
                false,
                ['decrypt']
            );

        const plain =
            await crypto.subtle.decrypt(
                {
                    name: 'AES-GCM',
                    iv: new Uint8Array(iv)
                },
                fileKey,
                cipher
            );

        const url = URL.createObjectURL(
            new Blob([plain], {
                type:
                    file.mime_type ||
                    'application/octet-stream'
            })
        );

        const a = document.createElement('a');

        a.href = url;
        a.download = file.original_name;

        document.body.appendChild(a);

        setTimeout(() => {
            a.click();

            document.body.removeChild(a);

            URL.revokeObjectURL(url);
        }, 100);

        ctx.toast(
            'File decrypted successfully 🔓',
            'success'
        );

    } catch (e) {
        ctx.toast(
            'Decrypt failed: ' + e.message,
            'error'
        );
    }
}