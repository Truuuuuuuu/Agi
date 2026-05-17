<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Protected by Agi
            </h2>

            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                         bg-emerald-50 dark:bg-emerald-950/60
                         text-emerald-700 dark:text-emerald-400
                         border border-emerald-200 dark:border-emerald-800">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Client-Side AES Encryption
            </span>
        </div>
    </x-slot>

    <div class="py-3" x-data="vaultApp()" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8  flex gap-5 w-full">
            
            {{-- ── Upload ─────────────────────────────────────────────────────── --}}
            <div class="w-full max-w-sm flex flex-col gap-3">
                <div class="bg-white p-5 rounded-3xl border">
                    <h3 class="font-semibold text-primary text-xl  mb-3">Upload File</h3>

                    <div class="border-2 border-dashed border-primary/40 text-primary/70 rounded-xl p-10 text-center cursor-pointer"
                        :class="isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/30' : ''"
                        @dragover.prevent="isDragging = true" @dragleave="isDragging = false"
                        @drop.prevent="handleDrop($event)" @click="$refs.fileInput.click()">

                        <p class="text-sm" x-text="dropLabel"></p>

                        <input type="file" class="hidden" x-ref="fileInput" @change="handleFileSelect($event)">
                    </div>

                    <div class="mt-4 flex gap-2" x-show="pendingFile && !uploading">
                        <button @click="encryptAndUpload" class="px-4 py-2 bg-primary text-white rounded-lg text-sm">
                            Encrypt & Upload
                        </button>

                        <button @click="clearPending" class="px-4 py-2 border rounded-lg text-sm">
                            Cancel
                        </button>
                    </div>
                </div>

                <div class="bg-primary rounded-3xl relative overflow-hidden h-52 flex flex-col justify-center items-start pl-5">
                    <img src="{{ asset('images/agi_logo.png') }}"
                        class="absolute bottom-0 right-0 w-52 h-52 object-contain opacity-20" />
                    <p class="text-xl font-bold text-white opacity-50">TOTAL FILES</p>    
                    <h1 class="text-8xl font-bold text-white" x-text="files.length"></h1>
                </div>


                <div x-show="uploading" class="mt-3 text-sm text-gray-500" x-text="uploadStep"></div>
            </div>

            {{-- ── File List ─────────────────────────────────────────────────── --}}
            <div class="flex-1 bg-white rounded-3xl border overflow-hidden">

                <div class="p-4 border-b flex justify-between">
                    <h3 class="font-semibold text-xl text-primary">My Files</h3>
                    <button @click="loadFiles"
                        class="text-sm text-white bg-primary rounded-3xl px-3 flex gap-1 items-center justify-center">
                        <span>Refresh</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="16" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="lucide lucide-refresh-ccw-icon lucide-refresh-ccw">
                            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                            <path d="M3 3v5h5" />
                            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16" />
                            <path d="M16 16h5v5" />
                        </svg>
                    </button>
                </div>

                <div x-show="loadingFiles" class="p-10 text-center text-gray-400">
                    Loading...
                </div>

                <div x-show="!loadingFiles && files.length === 0" class="p-10 text-center text-gray-400">
                    No files yet
                </div>

                <table class="w-full text-sm" x-show="files.length > 0">
                    <thead class="text-left border-b">
                        <tr class="text-primary">
                            <th class="p-3">File</th>
                            <th class="p-3">Type</th>
                            <th class="p-3">Uploaded</th>
                            <th class="p-3">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="file in files" :key="file.id">
                            <tr class="border-b hover:bg-gray-100">

                                <td class="p-3 font-medium" x-text="file.original_name"></td>

                                <td class="p-3 text-xs text-gray-500" x-text="file.mime_type"></td>

                                <td class="p-3 text-xs text-gray-500" x-text="file.uploaded_at"></td>

                                <td class="p-3 flex gap-2">

                                    <button @click="decryptAndDownload(file)" class="px-3 py-1 text-xs hover:scale-105 text-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
                                    </button>

                                    <button @click="confirmDelete(file)"
                                        class="px-3 py-1 text-xs  text-red-500 hover:scale-105">
                                        <svg xmlns="http://www.w3.org/2000/svg"  width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>

                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

        </div>


        {{-- ── DELETE MODAL ───────────────────────────────────────────── --}}
        <template x-teleport="body">
            <div x-show="deleteModal.show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white p-6 rounded-xl w-96 shadow-xl">
                    <h3 class="font-semibold text-lg mb-1">Delete File</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        Are you sure you want to delete
                        <span class="font-medium text-primary font-bold"
                            x-text="deleteModal.file?.original_name"></span>?
                        This cannot be undone.
                    </p>

                    <div class="flex justify-end gap-2">
                        <button @click="deleteModal = { show: false, file: null }"
                            class="px-4 py-2 text-sm border rounded-lg hover:scale-105 ">
                            Cancel
                        </button>
                        <button @click="deleteFile()" class="px-4 py-2 hover:scale-105 text-sm bg-red-500 text-white rounded-lg">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>



</x-app-layout>