<?php

namespace App\Support\LiveClasses;

use App\Models\LiveClass;
use App\Models\LiveClassMaterial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Resolves Live Class assets without allowing protected keys into the webroot. */
class LiveClassAssetStorage
{
    public const PRIVATE_PREFIX = 'live-class-private/live-classes';

    private const EXTENSION_MIMES = [
        'pdf' => ['application/pdf'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
        'doc' => ['application/msword', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'png' => ['image/png'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'mp4' => ['video/mp4', 'application/mp4'],
        'webm' => ['video/webm'],
        'mov' => ['video/quicktime'],
        'mkv' => ['video/x-matroska'],
        'mp3' => ['audio/mpeg'],
        'm4a' => ['audio/mp4', 'audio/x-m4a'],
    ];

    public function detectedMime(UploadedFile $file, string $extension): ?string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());
        return is_string($mime) && in_array(strtolower($mime), self::EXTENSION_MIMES[strtolower($extension)] ?? [], true)
            ? strtolower($mime)
            : null;
    }

    public function store(UploadedFile $file, LiveClass $liveClass, string $category, string $extension): string
    {
        $folder = $category === LiveClassMaterial::CATEGORY_RECORDING ? 'recordings' : 'materials';
        $key = self::PRIVATE_PREFIX.'/'.(int) $liveClass->school_id.'/'.(int) $liveClass->id.'/'.$folder.'/'.bin2hex(random_bytes(32)).'.'.strtolower($extension);
        try {
            $stored = $file->storeAs(dirname($key), basename($key), 'local');
        } catch (\Throwable $exception) {
            if (Storage::disk('local')->exists($key)) Storage::disk('local')->delete($key);
            throw $exception;
        }
        if ($stored !== $key || ! Storage::disk('local')->exists($key)) {
            if (Storage::disk('local')->exists($key)) Storage::disk('local')->delete($key);
            throw new \RuntimeException('The protected Live Class asset could not be stored.');
        }

        return $key;
    }

    public function isPrivateKey(string $key, LiveClass $liveClass, string $category): bool
    {
        $folder = $category === LiveClassMaterial::CATEGORY_RECORDING ? 'recordings' : 'materials';
        $pattern = '#^'.preg_quote(self::PRIVATE_PREFIX, '#').'/'.(int) $liveClass->school_id.'/'.(int) $liveClass->id.'/'.$folder.'/[a-f0-9]{64}\.[a-z0-9]{1,10}$#D';
        return (bool) preg_match($pattern, $key);
    }

    /** Returns a canonical path only for a matching, existing private key. */
    public function resolvePrivatePath(string $key, LiveClass $liveClass, string $category): ?string
    {
        if (! $this->isPrivateKey($key, $liveClass, $category)) return null;
        $root = realpath(Storage::disk('local')->path(self::PRIVATE_PREFIX));
        $candidate = realpath(Storage::disk('local')->path($key));
        if (! $root || ! $candidate || ! is_file($candidate)) return null;
        $prefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $comparisonRoot = DIRECTORY_SEPARATOR === '\\' ? strtolower($prefix) : $prefix;
        $comparisonCandidate = DIRECTORY_SEPARATOR === '\\' ? strtolower($candidate) : $candidate;
        return str_starts_with($comparisonCandidate, $comparisonRoot) ? $candidate : null;
    }

    public function deletePrivate(string $key, LiveClass $liveClass, string $category): bool
    {
        if (! $this->isPrivateKey($key, $liveClass, $category)) return false;
        return Storage::disk('local')->delete($key);
    }
}
