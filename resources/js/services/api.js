export async function fetchFilesPage(page = 1) {
    const resp = await fetch(`/api/files?page=${page}`, {
        headers: {
            Accept: 'application/json'
        }
    });

    if (!resp.ok) {
        throw new Error('Failed loading files');
    }

    return resp.json();
}

export async function uploadEncryptedFile(form) {
    const resp = await fetch('/api/files', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .content,
            Accept: 'application/json',
        },
        body: form,
    });

    if (!resp.ok) {
        throw new Error('Upload failed');
    }

    return resp.json();
}

export async function fetchFileMetadata(id) {
    const resp = await fetch(`/api/files/${id}`, {
        headers: {
            Accept: 'application/json'
        }
    });

    if (!resp.ok) {
        throw new Error('Download failed');
    }

    return resp.json();
}

export async function deleteFileRequest(id) {
    const resp = await fetch(`/api/files/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .content,
            Accept: 'application/json',
        },
    });

    if (!resp.ok) {
        throw new Error('Delete failed');
    }

    return true;
}