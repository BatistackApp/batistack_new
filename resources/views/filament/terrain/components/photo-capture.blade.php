{{-- Reusable Mobile Photo Capture Component --}}
{{--
    Usage: @include('filament.terrain.components.photo-capture', [
        'index' => 0,
        'label' => 'Photo du défaut',
        'model' => 'form.photos',
        'color' => 'amber',
    ])
--}}
<div x-data="photoCapture_{{ $index }}()" class="space-y-2">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label ?? 'Photo' }}</label>

    <!-- Capture Button -->
    <div class="flex items-center gap-3">
        <label for="photo-{{ $index }}"
            class="touch-target cursor-pointer inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-{{ $color ?? 'blue' }}-600 bg-{{ $color ?? 'blue' }}-50 rounded-lg hover:bg-{{ $color ?? 'blue' }}-100 dark:bg-{{ $color ?? 'blue' }}-900/20 dark:text-{{ $color ?? 'blue' }}-400 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Capturer
        </label>
        <input type="file" id="photo-{{ $index }}" accept="image/*" capture="environment"
            class="hidden" @change="capturePhoto($event)" />
    </div>

    <!-- Photo Preview Grid -->
    <div x-show="photos.length > 0" class="flex flex-wrap gap-2 mt-2">
        <template x-for="(photo, idx) in photos" :key="idx">
            <div class="relative group touch-target">
                <img :src="photo" class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-600" />
                <button @click="removePhoto(idx)" type="button"
                    class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center shadow-md active:scale-90 transition-transform">
                    ✕
                </button>
                <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-[10px] text-center py-0.5 rounded-b-lg"
                    x-text="photoSize(idx)"></div>
            </div>
        </template>
    </div>

    <!-- Compression Info -->
    <p x-show="photos.length > 0" class="text-xs text-gray-400" x-text="photos.length + ' photo(s) — compression auto appliquée'"></p>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('photoCapture_{{ $index }}', () => ({
                photos: [],
                max_width: 1024,
                quality: 0.8,

                async capturePhoto(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const compressed = await this.compressImage(file, this.max_width, this.quality);
                    this.photos.push(compressed);

                    // Update parent model if provided
                    @if(isset($model))
                        @if(str_contains($model, '.'))
                            @php $parts = explode('.', $model); @endphp
                            let obj = this;
                            @foreach(array_slice($parts, 0, -1) as $part)
                                obj = obj.{{ $part }};
                            @endforeach
                            obj.{{ end($parts) }} = [...this.photos];
                        @else
                            this.{{ $model }} = [...this.photos];
                        @endif
                    @endif

                    event.target.value = '';
                },

                removePhoto(idx) {
                    this.photos.splice(idx, 1);
                    @if(isset($model))
                        @if(str_contains($model, '.'))
                            @php $parts = explode('.', $model); @endphp
                            let obj = this;
                            @foreach(array_slice($parts, 0, -1) as $part)
                                obj = obj.{{ $part }};
                            @endforeach
                            obj.{{ end($parts) }} = [...this.photos];
                        @else
                            this.{{ $model }} = [...this.photos];
                        @endif
                    @endif
                },

                photoSize(idx) {
                    const photo = this.photos[idx];
                    if (!photo) return '';
                    const bytes = Math.round((photo.length - 'data:image/jpeg;base64,'.length) * 3 / 4);
                    return bytes > 1024 ? Math.round(bytes / 1024) + ' KB' : bytes + ' B';
                },

                compressImage(file, maxWidth, quality) {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const img = new Image();
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                let { width, height } = img;

                                if (width > maxWidth) {
                                    height = Math.round((height * maxWidth) / width);
                                    width = maxWidth;
                                }

                                canvas.width = width;
                                canvas.height = height;

                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, width, height);

                                resolve(canvas.toDataURL('image/jpeg', quality));
                            };
                            img.src = e.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                },
            }));
        });
    </script>
</div>
