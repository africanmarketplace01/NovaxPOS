<?php

namespace App\Http\Controllers\landlord;

use App\Http\Controllers\Controller;
use App\Jobs\CompressVideoJob;
use App\Models\landlord\VideoTutorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoTutorialController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = VideoTutorial::ordered();

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }
        if ($request->category) {
            $query->where('category', $request->category);
        }

        $tutorials  = $query->paginate(20)->withQueryString();
        $categories = VideoTutorial::distinct()->orderBy('category')->pluck('category');

        return view('superadmin.video-tutorials.index', compact('tutorials', 'categories'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function create()
    {
        $categories = VideoTutorial::distinct()->orderBy('category')->pluck('category');
        return view('superadmin.video-tutorials.create', compact('categories'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'category'     => 'required|string|max:100',
            'video_type'   => 'required|in:youtube,vimeo,upload',
            'video_url'    => 'required_if:video_type,youtube|required_if:video_type,vimeo|nullable|url|max:512',
            'video_file'   => 'required_if:video_type,upload|nullable|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:307200',
            'thumbnail'    => 'nullable|image|max:2048',
            'duration'     => 'nullable|string|max:20',
            'is_published' => 'nullable|boolean',
        ]);

        $data = [
            'title'        => $request->title,
            'description'  => $request->description,
            'category'     => trim($request->new_category ?: $request->category),
            'video_type'   => $request->video_type,
            'video_url'    => in_array($request->video_type, ['youtube', 'vimeo']) ? $request->video_url : null,
            'duration'     => $request->duration,
            'sort_order'   => VideoTutorial::max('sort_order') + 1,
            'is_published' => $request->boolean('is_published', true),
        ];

        // Store original file immediately — compression queued below
        if ($request->video_type === 'upload' && $request->hasFile('video_file')) {
            $data['video_path']         = $this->storeOriginal($request->file('video_file'));
            $data['compression_status'] = 'pending';
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = $request->file('thumbnail')
                ->store('video-tutorials/thumbnails', 'public');
        }

        $tutorial = VideoTutorial::create($data);

        // Dispatch compression to the queue worker (runs in the background)
        if ($tutorial->video_path) {
            CompressVideoJob::dispatch($tutorial->id);
        }

        return redirect()->route('superadmin.video-tutorials.index')
            ->with('success', 'Video tutorial saved! Compression is running in the background.');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function edit(VideoTutorial $videoTutorial)
    {
        $categories = VideoTutorial::distinct()->orderBy('category')->pluck('category');
        return view('superadmin.video-tutorials.edit', compact('videoTutorial', 'categories'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, VideoTutorial $videoTutorial)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'category'     => 'required|string|max:100',
            'video_type'   => 'required|in:youtube,vimeo,upload',
            'video_url'    => 'required_if:video_type,youtube|required_if:video_type,vimeo|nullable|url|max:512',
            'video_file'   => 'nullable|file|mimetypes:video/mp4,video/webm,video/ogg,video/quicktime|max:307200',
            'thumbnail'    => 'nullable|image|max:2048',
            'duration'     => 'nullable|string|max:20',
            'is_published' => 'nullable|boolean',
        ]);

        $data = [
            'title'        => $request->title,
            'description'  => $request->description,
            'category'     => trim($request->new_category ?: $request->category),
            'video_type'   => $request->video_type,
            'video_url'    => in_array($request->video_type, ['youtube', 'vimeo']) ? $request->video_url : $videoTutorial->video_url,
            'duration'     => $request->duration,
            'is_published' => $request->boolean('is_published', true),
        ];

        $dispatchCompression = false;

        if ($request->video_type === 'upload' && $request->hasFile('video_file')) {
            if ($videoTutorial->video_path) {
                Storage::disk('public')->delete($videoTutorial->video_path);
            }
            $data['video_path']         = $this->storeOriginal($request->file('video_file'));
            $data['compression_status'] = 'pending';
            $dispatchCompression        = true;
        }

        if ($request->hasFile('thumbnail')) {
            if ($videoTutorial->thumbnail_path) {
                Storage::disk('public')->delete($videoTutorial->thumbnail_path);
            }
            $data['thumbnail_path'] = $request->file('thumbnail')
                ->store('video-tutorials/thumbnails', 'public');
        }

        $videoTutorial->update($data);

        if ($dispatchCompression) {
            CompressVideoJob::dispatch($videoTutorial->id);
        }

        return redirect()->route('superadmin.video-tutorials.index')
            ->with('success', 'Tutorial updated! Compression running in the background.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(VideoTutorial $videoTutorial)
    {
        if ($videoTutorial->video_path) {
            Storage::disk('public')->delete($videoTutorial->video_path);
        }
        if ($videoTutorial->thumbnail_path) {
            Storage::disk('public')->delete($videoTutorial->thumbnail_path);
        }
        $videoTutorial->delete();

        return back()->with('success', 'Tutorial deleted.');
    }

    // ── Toggle Publish ────────────────────────────────────────────────────────

    public function togglePublish(VideoTutorial $videoTutorial)
    {
        $videoTutorial->update(['is_published' => !$videoTutorial->is_published]);
        return back()->with('success', 'Status updated.');
    }

    // ── Reorder ───────────────────────────────────────────────────────────────

    public function reorder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $position => $id) {
            VideoTutorial::where('id', $id)->update(['sort_order' => $position]);
        }
        return response()->json(['success' => true]);
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Store original file as-is (fast) — compression happens in the background.
     */
    private function storeOriginal(\Illuminate\Http\UploadedFile $file): string
    {
        $dir  = 'video-tutorials/videos';
        $name = Str::uuid() . '.' . $file->getClientOriginalExtension();
        Storage::disk('public')->makeDirectory($dir);
        return $file->storeAs($dir, $name, 'public');
    }
}
