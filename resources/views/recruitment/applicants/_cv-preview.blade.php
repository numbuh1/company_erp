@php
    $cvPath    = $recruitmentApplicant->cv_path ?? null;
    $cvUrl     = $cvPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($cvPath) : null;
    $cvExt     = $cvPath ? strtolower(pathinfo($cvPath, PATHINFO_EXTENSION)) : null;
    $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $sticky    = $cvPreviewSticky ?? true;
@endphp

<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 {{ $sticky ? 'lg:sticky lg:top-6' : '' }}">
    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Xem trước CV</h3>

    <div id="cv-preview-container">
        @if($cvUrl && in_array($cvExt, $imageExts))
            <img src="{{ $cvUrl }}" alt="CV Preview"
                class="max-w-full rounded border border-gray-200 dark:border-gray-700">
        @elseif($cvUrl && $cvExt === 'pdf')
            <iframe src="{{ $cvUrl }}"
                class="w-full rounded border border-gray-200 dark:border-gray-700" style="height: 70vh;"></iframe>
        @elseif($cvUrl && in_array($cvExt, ['doc', 'docx']))
            <iframe src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($cvUrl) }}"
                class="w-full rounded border border-gray-200 dark:border-gray-700" style="height: 70vh;"></iframe>
            <p class="text-xs text-gray-400 mt-2">
                Bản xem trước file Word/Doc cần URL có thể truy cập được từ Internet. Nếu không hiển thị được,
                vui lòng tải xuống để xem.
            </p>
        @elseif($cvUrl)
            <div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded text-center px-4">
                <p>Không có bản xem trước cho loại file này.</p>
                @if(isset($recruitmentApplicant))
                    <a href="{{ route('recruitment.applicants.cv.download', [$recruitmentPosition, $recruitmentApplicant]) }}"
                        class="mt-2 text-indigo-600 dark:text-indigo-400 hover:underline">Tải xuống CV</a>
                @endif
            </div>
        @else
            <div class="flex flex-col items-center justify-center h-64 text-sm text-gray-400 border border-dashed border-gray-300 dark:border-gray-600 rounded">
                <p>Chưa có file CV.</p>
            </div>
        @endif
    </div>
</div>
