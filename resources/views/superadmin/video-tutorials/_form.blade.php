{{-- Shared form partial for create & edit --}}
{{-- Variables: $videoTutorial (optional, for edit), $categories --}}

@php $editing = isset($videoTutorial); @endphp

<div class="row g-3">
    {{-- Title --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $videoTutorial->title ?? '') }}"
               placeholder="e.g. How to Create a Sale Invoice" required>
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Category --}}
    <div class="col-md-6">
        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
        <select name="category" id="categorySelect" class="form-select @error('category') is-invalid @enderror" required>
            @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ old('category', $videoTutorial->category ?? '') == $cat ? 'selected' : '' }}>
                    {{ $cat }}
                </option>
            @endforeach
            <option value="__new__">+ Add new category…</option>
        </select>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6" id="newCategoryWrap" style="display:none;">
        <label class="form-label fw-semibold">New Category Name</label>
        <input type="text" name="new_category" id="newCategoryInput" class="form-control"
               placeholder="e.g. Inventory Management">
    </div>

    {{-- Video Type --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Video Source <span class="text-danger">*</span></label>
        <div class="d-flex gap-3 flex-wrap">
            @foreach(['youtube' => ['fab fa-youtube text-danger', 'YouTube'], 'vimeo' => ['fab fa-vimeo text-info', 'Vimeo'], 'upload' => ['fas fa-upload text-secondary', 'Upload File']] as $val => $info)
            <div class="form-check">
                <input class="form-check-input" type="radio" name="video_type" id="vt_{{ $val }}"
                       value="{{ $val }}"
                       {{ old('video_type', $videoTutorial->video_type ?? 'youtube') == $val ? 'checked' : '' }}>
                <label class="form-check-label" for="vt_{{ $val }}">
                    <i class="{{ $info[0] }} me-1"></i>{{ $info[1] }}
                </label>
            </div>
            @endforeach
        </div>
    </div>

    {{-- URL field (YouTube / Vimeo) --}}
    <div class="col-12" id="urlWrap">
        <label class="form-label fw-semibold">Video URL <span class="text-danger">*</span></label>
        <input type="url" name="video_url" class="form-control @error('video_url') is-invalid @enderror"
               value="{{ old('video_url', $videoTutorial->video_url ?? '') }}"
               placeholder="https://www.youtube.com/watch?v=…">
        @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Paste the full YouTube or Vimeo watch URL.</div>
    </div>

    {{-- File upload --}}
    <div class="col-12" id="fileWrap" style="display:none;">
        <label class="form-label fw-semibold">Video File
            {{ $editing ? '(leave empty to keep current)' : '' }}
            {{ !$editing ? '<span class="text-danger">*</span>' : '' }}
        </label>
        <input type="file" name="video_file" id="videoFileInput"
               class="form-control @error('video_file') is-invalid @enderror"
               accept="video/mp4,video/webm,video/ogg,video/quicktime">
        @error('video_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            Accepted: MP4, WebM, OGG, MOV — max 300 MB.
            Video will be automatically compressed to 720p with FFmpeg if available.
        </div>
        @if($editing && $videoTutorial->video_path)
        <div class="mt-2 p-2 bg-light rounded d-flex align-items-center gap-2" style="font-size:.85rem;">
            <i class="fas fa-film text-muted"></i>
            <span>Current file: <code>{{ basename($videoTutorial->video_path) }}</code></span>
            <a href="{{ $videoTutorial->video_public_url }}" target="_blank" class="btn btn-xs btn-outline-secondary ms-auto py-0 px-2">
                <i class="fas fa-play me-1"></i>Preview
            </a>
        </div>
        @endif
    </div>

    {{-- Duration --}}
    <div class="col-md-4">
        <label class="form-label fw-semibold">Duration</label>
        <input type="text" name="duration" class="form-control @error('duration') is-invalid @enderror"
               value="{{ old('duration', $videoTutorial->duration ?? '') }}"
               placeholder="e.g. 5:30">
        @error('duration')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Optional — shown on the public page.</div>
    </div>

    {{-- Thumbnail --}}
    <div class="col-md-8">
        <label class="form-label fw-semibold">Custom Thumbnail</label>
        <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror"
               accept="image/*">
        @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Optional. YouTube videos auto-pull their thumbnail if left blank.</div>
        @if($editing && $videoTutorial->thumbnail_path)
        <div class="mt-2">
            <img src="{{ $videoTutorial->thumbnail_url }}" alt="thumbnail"
                 style="max-height:60px; border-radius:4px; border:1px solid #dee2e6;">
        </div>
        @endif
    </div>

    {{-- Description --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Description</label>
        <textarea name="description" rows="3"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Brief description shown below the video title…">{{ old('description', $videoTutorial->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Published --}}
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_published" id="isPublished" value="1"
                   {{ old('is_published', $videoTutorial->is_published ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="isPublished">
                Publish immediately (visible on public page)
            </label>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const select     = document.getElementById('categorySelect');
    const newWrap    = document.getElementById('newCategoryWrap');
    const newInput   = document.getElementById('newCategoryInput');
    const urlWrap    = document.getElementById('urlWrap');
    const fileWrap   = document.getElementById('fileWrap');
    const radios     = document.querySelectorAll('input[name="video_type"]');

    function toggleCategory() {
        if (select.value === '__new__') {
            newWrap.style.display = '';
            newInput.required = true;
        } else {
            newWrap.style.display = 'none';
            newInput.required = false;
            newInput.value = '';
        }
    }

    function toggleSource() {
        const val = document.querySelector('input[name="video_type"]:checked')?.value;
        urlWrap.style.display  = (val === 'upload') ? 'none' : '';
        fileWrap.style.display = (val === 'upload') ? ''     : 'none';
    }

    select.addEventListener('change', toggleCategory);
    radios.forEach(r => r.addEventListener('change', toggleSource));

    // Init
    toggleCategory();
    toggleSource();
})();
</script>
@endpush
