<div
    x-data="syncExport()"
    x-on:sync-download.window="saveFromServer($event.detail.url, $event.detail.filename)"
    class="fi-topbar-item"
>
    {{-- زر التفعيل (أيقونة السهمين) --}}
    <button
        type="button"
        class="fi-btn fi-btn-size-md fi-btn-variant-outline"
        title="مزامنة"
        x-on:click="$dispatch('open-modal', { id: 'sync-modal' })"
    >
        {{-- Heroicons: arrow-path (سهمين تدوير) --}}
              <x-filament::icon icon="heroicon-o-arrow-path" class="h-5 w-5 rtl:ml-1 ltr:mr-1" />
        <span class="hidden sm:inline">مزامنة</span>
    </button>

    {{-- مودال فيلمنت (مركزي وبنفس الثيم) --}}
    <x-filament::modal
        id="sync-modal"
        width="lg"
        :close-by-clicking-away="true"
        display-classes="block"
    >
        <x-slot name="heading">مزامنة البيانات</x-slot>
        <x-slot name="description">
            اختر الوضع ثم نفّذ العملية. عند التصدير سيتم إنشاء ملف ZIP وتنزيله.
        </x-slot>

        <div class="space-y-6">
            <div class="flex items-center gap-6">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="sync-mode" value="export" x-model="mode" class="fi-input-radio">
                    <span>تصدير (إنشاء ملف ZIP)</span>
                </label>

                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="sync-mode" value="import" x-model="mode" class="fi-input-radio">
                    <span>استيراد (من ZIP)</span>
                </label>
            </div>

            {{-- استيراد --}}
            <div x-show="mode === 'import'" x-cloak class="space-y-3">
                <div>
                    <label class="block text-sm font-medium mb-1">ملف ZIP</label>

                    <label class="flex items-center justify-center p-6 border-2 border-dashed rounded-xl
                                   bg-gray-50/30 dark:bg-white/5 hover:bg-gray-50/60 dark:hover:bg-white/10
                                   transition cursor-pointer">
                        <input
                            type="file"
                            wire:model.live="zip"
                            accept=".zip,application/zip,application/x-zip-compressed,application/octet-stream"
                            class="sr-only"
                            x-on:livewire-upload-start="uploading=true; uploadProgress=0"
                            x-on:livewire-upload-progress="uploadProgress=$event.detail.progress"
                            x-on:livewire-upload-error="uploading=false; uploadProgress=0"
                            x-on:livewire-upload-finish="uploading=false"
                        />
                        <div class="text-center text-sm text-gray-600 dark:text-gray-300">
                            اسحب وأفلت الملف هنا أو <span class="text-primary-600">اختر من جهازك</span>
                        </div>
                    </label>

                    @error('zip')
                        <div class="text-danger-600 text-sm mt-2">{{ $message }}</div>
                    @enderror
                </div>

                <template x-if="uploading">
                    <div>
                        <div class="h-2 bg-gray-200 dark:bg-gray-800 rounded">
                            <div class="h-2 rounded bg-primary-600" :style="`width:${uploadProgress}%;`"></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1" x-text="`جاري رفع الملف (${uploadProgress}%)...`"></div>
                    </div>
                </template>
            </div>

            {{-- تصدير --}}
            <div x-show="mode === 'export'" x-cloak class="text-xs text-gray-500">
                عند الضغط على “تنزيل الآن” سيتم إنشاء ملف المزامنة (ZIP) وتحميله.
            </div>
        </div>

        {{-- أزرار الفوتر (استخدم x-bind:disabled بدل :) --}}
        <x-slot name="footer">
            <div class="fi-modal-footer-actions gap-2">
                <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'sync-modal' })"
                    x-bind:disabled="importing || busy"
                >إلغاء</x-filament::button>

                <x-filament::button
                    x-show="mode === 'export'"
                    x-cloak
                    x-on:click="await pickLocation(); $wire.export(); $dispatch('close-modal', { id: 'sync-modal' })"
                    x-bind:disabled="busy || importing"
                >تنزيل الآن</x-filament::button>

                <x-filament::button
                    x-show="mode === 'import'"
                    x-cloak
                    x-on:click="startImport()"
                    x-bind:disabled="uploading || importing"
                >
                    <span x-show="!importing">تنفيذ الاستيراد</span>
                    <span x-show="importing" x-text="importBtnText"></span>
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</div>

<script>
function syncExport(){
    return {
        mode: 'export',
        busy: false,

        uploading: false,
        uploadProgress: 0,

        importing: false,
        importBtnText: '...جارٍ الاستيراد',

        saveHandle: null,

        async pickLocation(suggestedName = 'sync-export.zip'){
            if ('showSaveFilePicker' in window) {
                this.saveHandle = await window.showSaveFilePicker({
                    suggestedName,
                    types: [{ description: 'ZIP Archive', accept: { 'application/zip': ['.zip'] } }],
                    excludeAcceptAllOption: false,
                });
            } else {
                this.saveHandle = null;
            }
            this.busy = true;
        },

        async saveFromServer(url, filename){
            try {
                if (!this.saveHandle && 'showSaveFilePicker' in window) {
                    await this.pickLocation(filename || 'sync-export.zip');
                }
                const resp = await fetch(url, { credentials: 'include' });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const blob = await resp.blob();

                if (this.saveHandle) {
                    const writable = await this.saveHandle.createWritable();
                    await writable.write(blob);
                    await writable.close();
                    this.saveHandle = null;
                } else {
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(blob);
                    a.download = filename || 'sync-export.zip';
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                }
            } catch (e) {
                window.location = url;
            } finally {
                this.busy = false;
            }
        },

        async startImport(){
            this.importing = true;
            this.importBtnText = '...جارٍ الاستيراد';

            try {
                const res = await this.$wire.import();
                if (!res || !res.ok) {
                    this.importBtnText = 'فشل الاستيراد';
                    return;
                }
                this.importBtnText = 'تم';
                setTimeout(() => window.location.reload(), 600);
            } catch {
                this.importBtnText = 'فشل الاستيراد';
            }
        },
    }
}
</script>
