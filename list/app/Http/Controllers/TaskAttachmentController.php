<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TaskAttachment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    public function index()
    {
        $attachments = TaskAttachment::with('task')->latest()->get();
        return view('admin.attachments.index', compact('attachments'));
    }

    public function create()
    {
        $tasks = Task::all();
        return view('admin.attachments.create', compact('tasks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'file' => 'required|file|max:2048',
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments');

        TaskAttachment::create([
            'task_id' => $request->task_id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return redirect()->route('attachments.index')->with('success', 'Lampiran berhasil ditambahkan.');
    }

    public function destroy(TaskAttachment $attachment)
    {
        Storage::delete($attachment->file_path);
        $attachment->delete();

        return redirect()->route('attachments.index')->with('success', 'Lampiran berhasil dihapus.');
    }
}
