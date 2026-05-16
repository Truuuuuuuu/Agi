<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
        </div>
        <h3 class="font-semibold text-gray-800 dark:text-gray-100">Encrypt & Upload</h3>
    </div>

    <div class="p-6">

        {{-- Dropzone --}}
        <div
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="handleDrop($event)"
            :class="dragOver
                ? 'border-indigo-400 bg-indigo-50 dark:bg-indigo-950'
                : 'border-gray-200 dark:border-gray-600 hover:border-indigo-300 dark:hover:border-indigo-600'"
            class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer"
            @click="$refs.fileInput.click()"
        >
            <input
                type="file"
                x-ref="fileInput"
                class="hidden"
                multiple
                @change="handleFileSelect($event)"
            >

            <template x-if="!selectedFiles.length">
                <p class="text-sm text-gray-500">
                    Click or drag files here
                </p>
            </template>

            <template x-if="selectedFiles.length">
                <div class="text-left space-y-2">
                    <template x-for="file in selectedFiles" :key="file.name">
                        <div class="flex justify-between text-sm">
                            <span x-text="file.name"></span>
                            <span x-text="humanSize(file.size)"></span>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Password --}}
        <div class="mt-4">
            <input
                type="password"
                x-model="uploadPassword"
                placeholder="Encryption password"
                class="w-full rounded-lg border px-4 py-2 dark:bg-gray-700"
            >
        </div>

        {{-- Button --}}
        <button
            @click="uploadFiles()"
            class="mt-4 w-full bg-indigo-600 text-white py-2 rounded-lg"
        >
            Encrypt & Upload
        </button>

    </div>
</div>