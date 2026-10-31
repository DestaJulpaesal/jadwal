<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        // Mengambil kategori yang terkait dengan pengguna yang sedang login
        $categories = Category::latest()->where('user_id', Auth::id())->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'nama' => 'required|string|unique:categories,nama',
            'color' => 'required|string',
        ]);

        // Menyimpan kategori dengan menambahkan user_id dari pengguna yang sedang login
        Category::create([
            'nama' => $request->nama,
            'color' => $request->color,
            'user_id' => Auth::id(), // Menyimpan user_id yang sesuai dengan pengguna yang login
        ]);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category)
    {
        // Memastikan pengguna hanya bisa mengedit kategori mereka sendiri
        if ($category->user_id != Auth::id()) {
            return redirect()->route('categories.index')->with('error', 'Unauthorized action');
        }

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        // Memastikan pengguna hanya bisa mengedit kategori mereka sendiri
        if ($category->user_id != Auth::id()) {
            return redirect()->route('categories.index')->with('error', 'Unauthorized action');
        }

        // Validasi input
        $request->validate([
            'nama' => 'required|string|unique:categories,nama,' . $category->id,
            'color' => 'required|string',
        ]);

        // Update kategori
        $category->update([
            'nama' => $request->nama,
            'color' => $request->color,
        ]);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        // Memastikan pengguna hanya bisa menghapus kategori mereka sendiri
        if ($category->user_id != Auth::id()) {
            return redirect()->route('categories.index')->with('error', 'Unauthorized action');
        }

        // Menghapus kategori
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }

    public function show(Category $category)
    {
        // Memastikan pengguna hanya bisa melihat kategori mereka sendiri
        if ($category->user_id != Auth::id()) {
            return redirect()->route('categories.index')->with('error', 'Unauthorized action');
        }

        return view('admin.categories.show', compact('category'));
    }
}
