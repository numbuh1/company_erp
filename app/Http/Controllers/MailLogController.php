<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\MailLog;
use Illuminate\Http\Request;

class MailLogController extends Controller
{
    private function authorize(): void
    {
        if (! auth()->user()->can('manage settings')) abort(403);
    }

    public function index(Request $request)
    {
        $this->authorize();

        $query = MailLog::latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('to', 'like', "%{$search}%");
            });
        }

        $mailLogs = $query->paginate(25)->withQueryString();
        $loggingEnabled = AppSetting::get('mail_logging_enabled', '1') === '1';

        return view('admin.mail-logs.index', compact('mailLogs', 'loggingEnabled'));
    }

    public function show(MailLog $mailLog)
    {
        $this->authorize();
        return view('admin.mail-logs.show', compact('mailLog'));
    }

    public function preview(MailLog $mailLog)
    {
        $this->authorize();
        return response($mailLog->body ?? '<p>No content</p>')
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function toggleLogging(Request $request)
    {
        $this->authorize();

        $enabled = $request->boolean('enabled') ? '1' : '0';
        AppSetting::set('mail_logging_enabled', $enabled);

        return back()->with('success', $enabled === '1'
            ? __('Mail logging enabled.')
            : __('Mail logging disabled.'));
    }
}
