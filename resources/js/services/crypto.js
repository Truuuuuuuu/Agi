export async function initUserKeys(ctx) {
    const stored = localStorage.getItem('user_keypair');

    if (stored) {
        const parsed = JSON.parse(stored);

        ctx.privateKey = await crypto.subtle.importKey(
            'jwk',
            parsed.privateKey,
            {
                name: 'RSA-OAEP',
                hash: 'SHA-256'
            },
            true,
            ['decrypt']
        );

        ctx.publicKey = await crypto.subtle.importKey(
            'jwk',
            parsed.publicKey,
            {
                name: 'RSA-OAEP',
                hash: 'SHA-256'
            },
            true,
            ['encrypt']
        );

        ctx.keyReady = true;
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

    const publicJwk = await crypto.subtle.exportKey(
        'jwk',
        pair.publicKey
    );

    const privateJwk = await crypto.subtle.exportKey(
        'jwk',
        pair.privateKey
    );

    localStorage.setItem(
        'user_keypair',
        JSON.stringify({
            publicKey: publicJwk,
            privateKey: privateJwk
        })
    );

    ctx.privateKey = pair.privateKey;
    ctx.publicKey = pair.publicKey;

    await fetch('/api/user/public-key', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .content
        },
        body: JSON.stringify({
            public_key: publicJwk
        })
    });

    ctx.keyReady = true;
}

export async function generateFileKey() {
    return crypto.subtle.generateKey(
        {
            name: 'AES-GCM',
            length: 256
        },
        true,
        ['encrypt', 'decrypt']
    );
}

export async function encryptFile(fileKey, iv, plain) {
    return crypto.subtle.encrypt(
        {
            name: 'AES-GCM',
            iv
        },
        fileKey,
        plain
    );
}

export async function decryptFile(fileKey, iv, cipher) {
    return crypto.subtle.decrypt(
        {
            name: 'AES-GCM',
            iv
        },
        fileKey,
        cipher
    );
}