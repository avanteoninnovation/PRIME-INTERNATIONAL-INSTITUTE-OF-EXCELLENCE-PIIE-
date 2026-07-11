<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLiveClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'exists:subjects,id'],
            'class_id' => ['nullable', 'exists:classes,id'],
            'programme_id' => ['nullable', 'exists:programmes,id'],
            'academic_session_id' => ['nullable', 'exists:sessions,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'platform' => ['required', 'in:jitsi,google_meet,zoom,bigbluebutton,custom'],
            'meeting_url' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
            'meeting_id' => ['nullable', 'string', 'max:150'],
            'meeting_password' => ['nullable', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'timezone' => ['nullable', 'timezone'],
            'status' => ['nullable', 'in:draft,scheduled,live,ended,cancelled'],
            'is_published' => ['nullable', 'boolean'],
            'attendance_enabled' => ['nullable', 'boolean'],
            'recording_url' => ['nullable', 'url', 'starts_with:https://', 'max:500'],
        ];
    }
}
