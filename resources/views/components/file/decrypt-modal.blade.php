<div
    x-show="decryptModal.open"
    class="fixed inset-0 bg-black/50 flex items-center justify-center"
>

    <div class="bg-white dark:bg-gray-800 p-6 rounded-xl w-full max-w-md">

        <h3 class="font-semibold mb-4">Decrypt File</h3>

        <p class="text-sm mb-3" x-text="decryptModal.file?.original_name"></p>

        <input
            type="password"
            x-model="decryptModal.password"
            class="w-full border rounded px-3 py-2 dark:bg-gray-700"
            placeholder="Password"
            @keydown.enter="decryptAndDownload()"
        >

        <p x-show="decryptModal.error"
           class="text-red-500 text-sm mt-2"
           x-text="decryptModal.error"></p>

        <div class="flex gap-2 mt-4">
            <button
                @click="decryptModal.open = false"
                class="flex-1 border py-2 rounded"
            >
                Cancel
            </button>

            <button
                @click="decryptAndDownload()"
                class="flex-1 bg-indigo-600 text-white py-2 rounded"
            >
                Decrypt
            </button>
        </div>

    </div>
</div>