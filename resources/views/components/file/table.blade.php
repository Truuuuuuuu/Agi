<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border overflow-hidden">

    {{-- Header --}}
    <div class="px-6 py-4 border-b flex justify-between items-center">
        <h3 class="font-semibold text-gray-800 dark:text-gray-100">
            Your Files
            <span class="text-xs text-gray-400" x-text="'(' + files.length + ')'"></span>
        </h3>

        <input
            type="search"
            x-model="search"
            placeholder="Search..."
            class="text-sm border rounded px-3 py-1 dark:bg-gray-700"
        >
    </div>

    {{-- Empty --}}
    <div x-show="!filteredFiles.length" class="p-10 text-center text-gray-400">
        No files yet
    </div>

    {{-- List --}}
    <div class="divide-y">
        <template x-for="file in filteredFiles" :key="file.id">
            <div class="px-6 py-4 flex justify-between items-center">

                <div>
                    <p class="font-medium" x-text="file.original_name"></p>
                    <p class="text-xs text-gray-400">
                        <span x-text="file.human_size"></span>
                        · <span x-text="file.created_at"></span>
                    </p>
                </div>

                <div class="flex gap-2">
                    <button @click="openDecryptModal(file)">🔓</button>
                    <button @click="deleteFile(file)">🗑</button>
                </div>

            </div>
        </template>
    </div>

</div>