{{--
 * Componente da aplicação LivreOS
 *
 * @author    viniciusvams
 * @copyright 2024-2026 LivreOS
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt AGPL-3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 --}}

<x-common.component-card title="Dropzone">
    <!-- Dropzone -->
    <div 
        x-data="{
            isDragging: false,
            files: [],
            handleDrop(e) {
                this.isDragging = false;
                const droppedFiles = Array.from(e.dataTransfer.files);
                this.handleFiles(droppedFiles);
            },
            handleFiles(selectedFiles) {
                const validTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
                const validFiles = selectedFiles.filter(file => validTypes.includes(file.type));
                
                if (validFiles.length > 0) {
                    this.files = [...this.files, ...validFiles];
                    console.log('Files uploaded:', validFiles);
                    
                    // Here you can add logic to upload files to server
                    this.uploadFiles(validFiles);
                }
            },
            uploadFiles(files) {
                // Implement your file upload logic here
                // Example: Use FormData and fetch/axios to upload
                console.log('Uploading files:', files);
            },
            removeFile(index) {
                this.files.splice(index, 1);
            }
        }"
        class="transition border border-gray-300 border-dashed cursor-pointer dark:hover:border-brand-500 dark:border-gray-700 rounded-xl hover:border-brand-500"
    >
        <div 
            @drop.prevent="handleDrop($event)"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @click="$refs.fileInput.click()"
            :class="isDragging 
                ? 'border-brand-500 bg-gray-100 dark:bg-gray-800' 
                : 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900'"
            class="dropzone rounded-xl border-dashed border-gray-300 p-7 lg:p-10 transition-colors cursor-pointer"
            id="demo-upload"
        >
            <!-- Hidden File Input -->
            <input 
                x-ref="fileInput"
                type="file" 
                @change="handleFiles(Array.from($event.target.files)); $event.target.value = ''"
                accept="image/png,image/jpeg,image/gif,image/webp,image/avif,image/heic,image/heif,image/svg+xml"
                multiple
                class="hidden"
                @click.stop
            />

            <div class="flex flex-col items-center m-0">
                <!-- Icon Container -->
                <div class="mb-[22px] flex justify-center">
                    <div class="flex h-[68px] w-[68px] items-center justify-center rounded-full bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        <svg
                            class="fill-current"
                            width="29"
                            height="28"
                            viewBox="0 0 29 28"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M14.5019 3.91699C14.2852 3.91699 14.0899 4.00891 13.953 4.15589L8.57363 9.53186C8.28065 9.82466 8.2805 10.2995 8.5733 10.5925C8.8661 10.8855 9.34097 10.8857 9.63396 10.5929L13.7519 6.47752V18.667C13.7519 19.0812 14.0877 19.417 14.5019 19.417C14.9161 19.417 15.2519 19.0812 15.2519 18.667V6.48234L19.3653 10.5929C19.6583 10.8857 20.1332 10.8855 20.426 10.5925C20.7188 10.2995 20.7186 9.82463 20.4256 9.53184L15.0838 4.19378C14.9463 4.02488 14.7367 3.91699 14.5019 3.91699ZM5.91626 18.667C5.91626 18.2528 5.58047 17.917 5.16626 17.917C4.75205 17.917 4.41626 18.2528 4.41626 18.667V21.8337C4.41626 23.0763 5.42362 24.0837 6.66626 24.0837H22.3339C23.5766 24.0837 24.5839 23.0763 24.5839 21.8337V18.667C24.5839 18.2528 24.2482 17.917 23.8339 17.917C23.4197 17.917 23.0839 18.2528 23.0839 18.667V21.8337C23.0839 22.2479 22.7482 22.5837 22.3339 22.5837H6.66626C6.25205 22.5837 5.91626 22.2479 5.91626 21.8337V18.667Z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Text Content -->
                <h4 class="mb-3 font-semibold text-gray-800 text-theme-xl dark:text-white/90">
                    <span x-show="!isDragging">Drag & Drop Files Here</span>
                    <span x-show="isDragging" x-cloak>Drop Files Here</span>
                </h4>

                <span class="text-center mb-5 block w-full max-w-[290px] text-sm text-gray-700 dark:text-gray-400">
                    Drag and drop your PNG, JPG, WebP, SVG images here or browse
                </span>

                <span class="font-medium underline text-theme-sm text-brand-500">
                    Browse File
                </span>
            </div>
        </div>

        <!-- File Preview List (Optional) -->
        <div x-show="files.length > 0" class="mt-4 p-4 border-t border-gray-200 dark:border-gray-700" x-cloak>
            <h5 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Uploaded Files:</h5>
            <ul class="space-y-2">
                <template x-for="(file, index) in files" :key="index">
                    <li class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="file.name"></span>
                        </div>
                        <button 
                            @click.stop="removeFile(index)"
                            type="button"
                            class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</x-common.component-card>
