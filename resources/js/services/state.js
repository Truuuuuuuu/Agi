export function createVaultState() {
    return {
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

        hashModal: {
            show: false,
            file: null
        },

        deleteModal: {
            show: false,
            file: null
        },

        toasts: [],

        currentPage: 1,
        lastPage: 1,
        totalFiles: 0,
    };
}