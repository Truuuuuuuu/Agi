<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Secure Vault') }}
            </h2>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                         bg-emerald-50 dark:bg-emerald-950/60
                         text-emerald-700 dark:text-emerald-400
                         border border-emerald-200 dark:border-emerald-800">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                AES-256-GCM Active
            </span>
        </div>
    </x-slot>

    <div class="py-8" x-data="vaultApp()" x-init="init()">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- ── Security Info Banner ─────────────────────────────────────────── --}}
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="h-0.5 bg-gradient-to-r from-indigo-500 via-sky-400 to-transparent"></div>
                <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4">

                    {{-- Key status --}}
                    <div class="flex-1 min-w-0">
                        <p
                            class="text-xs font-semibold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mb-1">
                            Encryption Key
                        </p>
                        <template x-if="keyReady">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    🔑 Device key loaded
                                </span>
                                <span class="font-mono text-xs text-gray-400 dark:text-gray-500 truncate"
                                    x-text="keyFingerprint"></span>
                            </div>
                        </template>
                        <template x-if="!keyReady">
                            <div class="flex items-center gap-2">
                                <span
                                    class="inline-block w-3 h-3 border-2 border-gray-300 border-t-indigo-500 rounded-full animate-spin"></span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">Generating encryption key…</span>
                            </div>
                        </template>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Your key is stored in this browser only. Files are encrypted before upload — the server
                            never sees plaintext.
                        </p>
                    </div>

                    {{-- Key actions --}}
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="exportKey" class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                                   text-xs text-gray-600 dark:text-gray-400
                                   hover:text-indigo-600 hover:border-indigo-400
                                   dark:hover:text-indigo-400 dark:hover:border-indigo-500
                                   transition">
                            ⬇ Export Key
                        </button>
                        <label class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600
                                      text-xs text-gray-600 dark:text-gray-400
                                      hover:text-indigo-600 hover:border-indigo-400
                                      dark:hover:text-indigo-400 dark:hover:border-indigo-500
                                      transition cursor-pointer">
                            ⬆ Import Key
                            <input type="file" accept=".json" class="hidden" @change="importKey($event)">
                        </label>
                    </div>
                </div>
            </div>

            {{-- ── Stats Row ────────────────────────────────────────────────────── --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p
                        class="text-xs font-semibold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mb-1">
                        Algorithm</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">AES-256-GCM</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p
                        class="text-xs font-semibold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mb-1">
                        Auth Encryption</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">Tamper-proof</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p
                        class="text-xs font-semibold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mb-1">
                        Integrity</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100">SHA-256</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                    <p
                        class="text-xs font-semibold uppercase tracking-widest text-indigo-500 dark:text-indigo-400 mb-1">
                        Files Stored</p>
                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="files.length"></p>
                </div>
            </div>

            {{-- ── Upload Zone ──────────────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-indigo-500"></span>
                    Upload File
                </h3>

                {{-- Drop zone --}}
                <div class="border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-all select-none"
                    :class="isDragging
                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30'
                        : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500 hover:bg-gray-50 dark:hover:bg-gray-700/30'"
                    @dragover.prevent="isDragging = true" @dragleave.self="isDragging = false"
                    @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()">
                    <div class="text-3xl mb-2" x-text="pendingFile ? '📄' : '📁'"></div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="dropLabel"></p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Encrypted with AES-256-GCM in your browser before upload
                    </p>

                    {{-- Progress --}}
                    <div x-show="uploading" x-cloak
                        class="mt-4 h-1 w-full bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-indigo-500 to-sky-400 rounded-full transition-[width] duration-300"
                            :style="`width:${uploadPct}%`"></div>
                    </div>
                    <p x-show="uploading" x-cloak class="text-xs text-indigo-500 dark:text-indigo-400 mt-2 font-medium"
                        x-text="uploadStep"></p>

                    <input type="file" x-ref="fileInput" class="hidden" @change="handleFileSelect($event)">
                </div>

                {{-- Upload action --}}
                <div x-show="pendingFile && !uploading" x-cloak class="flex items-center gap-3 mt-4">
                    <button @click="encryptAndUpload" :disabled="!keyReady" class="flex items-center gap-2 px-4 py-2 rounded-lg
                               bg-indigo-600 hover:bg-indigo-500
                               disabled:opacity-40 disabled:cursor-not-allowed
                               text-white text-sm font-medium transition">
                        🔒 Encrypt &amp; Upload
                    </button>
                    <button @click="clearPending" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                               text-sm text-gray-600 dark:text-gray-400
                               hover:text-red-500 hover:border-red-400 transition">
                        Cancel
                    </button>
                    <span class="text-xs text-gray-400 dark:text-gray-500"
                        x-text="pendingFile ? pendingFile.name : ''"></span>
                </div>
            </div>

            {{-- ── File List ────────────────────────────────────────────────────── --}}
            <div
                class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-indigo-500"></span>
                        My Encrypted Files
                    </h3>
                    <button @click="loadFiles" :disabled="loadingFiles" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                               border border-gray-300 dark:border-gray-600
                               text-xs text-gray-600 dark:text-gray-400
                               hover:text-indigo-500 hover:border-indigo-400
                               disabled:opacity-40 transition">
                        <span x-show="loadingFiles"
                            class="inline-block w-3 h-3 border border-gray-300 border-t-indigo-500 rounded-full animate-spin"></span>
                        🔄 Refresh
                    </button>
                </div>

                {{-- Loading state --}}
                <div x-show="loadingFiles" x-cloak
                    class="flex flex-col items-center justify-center py-16 gap-3 text-gray-400 dark:text-gray-500">
                    <div
                        class="w-7 h-7 border-2 border-gray-200 dark:border-gray-700 border-t-indigo-500 rounded-full animate-spin">
                    </div>
                    <p class="text-sm">Loading…</p>
                </div>

                {{-- Empty state --}}
                <div x-show="!loadingFiles && files.length === 0" x-cloak
                    class="flex flex-col items-center justify-center py-16 gap-2 text-gray-400 dark:text-gray-500">
                    <span class="text-4xl opacity-30">🗂️</span>
                    <p class="text-sm">No files yet — upload your first file above.</p>
                </div>

                {{-- Table --}}
                <div x-show="!loadingFiles && files.length > 0" x-cloak class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs font-semibold uppercase tracking-wider
                                       text-gray-500 dark:text-gray-400
                                       border-b border-gray-100 dark:border-gray-700">
                                <th class="px-5 py-3 text-left font-semibold">File</th>
                                <th class="px-5 py-3 text-left font-semibold">Size</th>
                                <th class="px-5 py-3 text-left font-semibold">SHA-256 Hash</th>
                                <th class="px-5 py-3 text-left font-semibold">Uploaded</th>
                                <th class="px-5 py-3 text-left font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <template x-for="file in files" :key="file.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">

                                    {{-- Name + type --}}
                                    <td class="px-5 py-3.5 max-w-[220px]">
                                        <p class="font-medium text-gray-900 dark:text-gray-100 truncate"
                                            x-text="file.original_name"></p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 font-mono truncate mt-0.5"
                                            x-text="file.mime_type"></p>
                                        <span class="inline-flex items-center gap-1 mt-1.5 px-2 py-0.5 rounded-full text-xs font-medium
                                                     bg-indigo-50 dark:bg-indigo-950/50
                                                     text-indigo-600 dark:text-indigo-400
                                                     border border-indigo-200 dark:border-indigo-800/60">
                                            🔒 Encrypted
                                        </span>
                                    </td>

                                    {{-- Size --}}
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono"
                                            x-text="file.size"></span>
                                    </td>

                                    {{-- Hash --}}
                                    <td class="px-5 py-3.5">
                                        <button @click="showHashModal(file)" class="font-mono text-xs px-2 py-1 rounded
                                                   bg-sky-50 dark:bg-sky-950/40
                                                   text-sky-600 dark:text-sky-400
                                                   border border-sky-200 dark:border-sky-800/60
                                                   hover:bg-sky-100 dark:hover:bg-sky-900/50
                                                   transition max-w-[140px] truncate block text-left"
                                            :title="file.sha256_hash" x-text="file.sha256_hash.slice(0, 12) + '…'">
                                        </button>
                                    </td>

                                    {{-- Uploaded --}}
                                    <td class="px-5 py-3.5 whitespace-nowrap">
                                        <span class="text-xs text-gray-500 dark:text-gray-400"
                                            x-text="file.uploaded_at"></span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <button @click="decryptAndDownload(file)"
                                                :disabled="!keyReady || file.decrypting" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg
                                                       border border-gray-300 dark:border-gray-600
                                                       text-xs font-medium
                                                       text-gray-700 dark:text-gray-300
                                                       hover:text-indigo-600 hover:border-indigo-400
                                                       dark:hover:text-indigo-400 dark:hover:border-indigo-500
                                                       disabled:opacity-40 disabled:cursor-not-allowed
                                                       transition">
                                                <span x-show="file.decrypting"
                                                    class="inline-block w-3 h-3 border border-gray-300 border-t-indigo-500 rounded-full animate-spin"></span>
                                                <span x-text="file.decrypting ? 'Decrypting…' : '⬇ Download'"></span>
                                            </button>
                                            <button @click="confirmDelete(file)" class="px-2.5 py-1.5 rounded-lg
                                                       border border-red-200 dark:border-red-900/50
                                                       text-xs text-red-500
                                                       hover:bg-red-50 dark:hover:bg-red-950/30
                                                       transition">
                                                🗑
                                            </button>
                                        </div>

                                        {{-- Inline integrity result --}}
                                        <p x-show="file.integrityOk === true" x-cloak
                                            class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-medium">
                                            ✅ Integrity verified
                                        </p>
                                        <p x-show="file.integrityOk === false" x-cloak
                                            class="text-xs text-red-500 mt-1 font-medium">
                                            ⚠️ Integrity check failed
                                        </p>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Hash Detail Modal ────────────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="hashModal.show" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4"
            @click.self="hashModal.show = false">
            <div x-show="hashModal.show" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700
                        shadow-2xl p-6 w-full max-w-md">

                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-0.5">
                    🧮 SHA-256 Integrity Hash
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4"
                    x-text="hashModal.file ? hashModal.file.original_name : ''"></p>

                <div class="font-mono text-xs break-all leading-relaxed
                            bg-sky-50 dark:bg-sky-950/30
                            border border-sky-200 dark:border-sky-800
                            text-sky-700 dark:text-sky-300
                            rounded-lg p-3 mb-4" x-text="hashModal.file ? hashModal.file.sha256_hash : ''"></div>

                <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
                    Computed from the original file <strong>before</strong> encryption.
                    After decryption, the app recomputes and compares — a match confirms the file arrived intact.
                </p>

                <div class="flex justify-end gap-2">
                    <button @click="copyToClipboard(hashModal.file.sha256_hash)" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                               text-sm text-gray-600 dark:text-gray-400
                               hover:text-indigo-500 hover:border-indigo-400 transition">
                        📋 Copy
                    </button>
                    <button @click="hashModal.show = false" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500
                               text-white text-sm font-medium transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ── Delete Confirm Modal ─────────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div x-show="deleteModal.show" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4"
            @click.self="deleteModal.show = false">
            <div x-show="deleteModal.show" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700
                        shadow-2xl p-6 w-full max-w-sm">

                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-1">Delete File</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
                    "<span x-text="deleteModal.file ? deleteModal.file.original_name : ''"></span>" will be permanently
                    removed from the server. This cannot be undone.
                </p>
                <div class="flex justify-end gap-2">
                    <button @click="deleteModal.show = false" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                               text-sm text-gray-600 dark:text-gray-400
                               hover:border-gray-400 transition">
                        Cancel
                    </button>
                    <button @click="deleteFile" class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-500
                               text-white text-sm font-medium transition">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </template>

    {{-- ── Toast Stack ──────────────────────────────────────────────────────────── --}}
    <template x-teleport="body">
        <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">
            <template x-for="t in toasts" :key="t.id">
                <div x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="pointer-events-auto px-4 py-2.5 rounded-xl text-sm font-medium shadow-lg max-w-xs" :class="{
                         'bg-emerald-50 dark:bg-emerald-950/90 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300': t.type === 'success',
                         'bg-red-50    dark:bg-red-950/90    border border-red-200    dark:border-red-800    text-red-600    dark:text-red-400':    t.type === 'error',
                         'bg-indigo-50 dark:bg-indigo-950/90 border border-indigo-200 dark:border-indigo-800 text-indigo-600 dark:text-indigo-400': t.type === 'info',
                     }" x-text="t.msg">
                </div>
            </template>
        </div>
    </template></x-app-layout>