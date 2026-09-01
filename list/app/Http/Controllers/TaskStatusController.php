<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use Illuminate\Http\Request;

class TaskStatusController extends Controller
{
    public function index()
    {
        $statuses = TaskStatus::all();
        return view('admin.statuses.index', compact('statuses'));
    }

    public function create()
    {
        return view('admin.statuses.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:task_statuses,name|max:50',
        ]);

        TaskStatus::create($request->only('name'));

        return redirect()->route('statuses.index')->with('success', 'Status berhasil ditambahkan.');
    }

    public function show(TaskStatus $status)
    {
        return view('admin.statuses.show', compact('status'));
    }

    public function edit(TaskStatus $status)
    {
        return view('admin.statuses.edit', compact('status'));
    }

    public function update(Request $request, TaskStatus $status)
    {
        $request->validate([
            'name' => 'required|max:50|unique:task_statuses,name,' . $status->id,
        ]);

        $status->update($request->only('name'));

        return redirect()->route('statuses.index')->with('success', 'Status berhasil diperbarui.');
    }

    public function destroy(TaskStatus $status)
    {
        $status->delete();
        return redirect()->route('statuses.index')->with('success', 'Status berhasil dihapus.');
    }
}
