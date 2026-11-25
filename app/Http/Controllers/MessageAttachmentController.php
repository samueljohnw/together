<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    /**
     * Download the attachment under its original file name.
     */
    public function show(Message $message): StreamedResponse
    {
        abort_unless($message->hasAttachment() && Storage::exists($message->attachment_path), 404);

        return Storage::download($message->attachment_path, $message->attachment_name);
    }

    /**
     * Show an image or video attachment inline, with byte-range support so videos can seek.
     *
     * The type is checked from the stored file's contents, so the browser only renders real images and videos.
     */
    public function preview(Message $message): BinaryFileResponse
    {
        abort_unless($message->mediaKind() !== null && Storage::exists($message->attachment_path), 404);

        $detectedType = Storage::mimeType($message->attachment_path);

        abort_unless(Message::mediaKindOf($detectedType) === $message->mediaKind(), 404);

        return response()->file(Storage::path($message->attachment_path), [
            'Content-Type' => $detectedType === 'application/mp4' ? 'video/mp4' : $detectedType,
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ])->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $message->attachment_name,
            str_replace(['/', '\\', '%'], '', Str::ascii($message->attachment_name)) ?: 'attachment',
        );
    }
}
