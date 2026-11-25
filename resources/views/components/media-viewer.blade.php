{{-- Full-screen viewer for image and video attachments. Driven by openMediaViewer() in resources/js/app.js. --}}
<div data-media-viewer hidden class="fixed inset-0 z-[60] flex-col bg-[#161915]/90 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="media-viewer-title">
    <header class="flex shrink-0 items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <div class="min-w-0">
            <h2 id="media-viewer-title" data-media-viewer-title class="truncate text-[14px] font-medium text-white"></h2>
            <p data-media-viewer-meta class="mt-0.5 truncate text-[12px] text-white/60"></p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <a data-media-viewer-download href="#" class="inline-flex h-9 items-center rounded-lg bg-white/10 px-3.5 text-[13px] font-medium text-white transition hover:bg-white/20">Download</a>
            <button type="button" data-media-viewer-close class="flex h-9 w-9 items-center justify-center rounded-lg text-white/80 transition hover:bg-white/10 hover:text-white" aria-label="Close viewer">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
            </button>
        </div>
    </header>
    <div data-media-viewer-stage class="flex min-h-0 flex-1 items-center justify-center px-4 pb-6 sm:px-8"></div>
</div>
