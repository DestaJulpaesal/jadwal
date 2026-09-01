<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Category;
use App\Models\Tag;
use App\Models\TaskStatus;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::with(['category', 'tags', 'status'])->latest()->get();
        return view('admin.tasks.index', compact('tasks'));
    }

    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();
        $statuses = TaskStatus::all();

        return view('admin.tasks.create', compact('categories', 'tags', 'statuses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'task_status_id' => 'nullable|exists:task_statuses,id',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'user_id' => auth()->id(),
            'category_id' => $request->category_id,
            'task_status_id' => $request->task_status_id,
            'due_date' => $request->due_date,
        ]);

        if ($request->has('tags')) {
            $task->tags()->sync($request->tags);
        }

        return redirect()->route('admin.tasks.index')->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        return view('admin.tasks.show', compact('task'));
    }

    public function edit(Task $task)
    {
        $categories = Category::all();
        $tags = Tag::all();
        $statuses = TaskStatus::all();

        return view('admin.tasks.edit', compact('task', 'categories', 'tags', 'statuses'));
    }

    public function update(Request $request, Task $task)
    {
        $request->validate([
            'title' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'task_status_id' => 'nullable|exists:task_statuses,id',
            'due_date' => 'nullable|date',
        ]);

        $task->update([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'task_status_id' => $request->task_status_id,
            'due_date' => $request->due_date,
        ]);

        if ($request->has('tags')) {
            $task->tags()->sync($request->tags);
        }

        return redirect()->route('admin.tasks.index')->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();
        return redirect()->route('admin.tasks.index')->with('success', 'Task deleted.');
    }
}
