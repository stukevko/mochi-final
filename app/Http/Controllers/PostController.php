<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $type = $request->query('type');

        $posts = Post::query()
            ->published()
            ->with('category')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner
                        ->where('title', 'like', '%'.$q.'%')
                        ->orWhere('excerpt', 'like', '%'.$q.'%')
                        ->orWhere('body', 'like', '%'.$q.'%');
                });
            })
            ->when(
                $category !== null && $category !== '',
                fn ($query) => $query->where('post_category_id', $category),
            )
            ->when(
                $type !== null && $type !== '' && PostType::tryFrom((string) $type),
                fn ($query) => $query->where('type', $type),
            )
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('posts.index', [
            'posts' => $posts,
            'categories' => PostCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'filters' => [
                'q' => $q,
                'category' => $category,
                'type' => $type,
            ],
            'postTypes' => PostType::cases(),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless(
            $post->is_published && $post->published_at && $post->published_at->isPast(),
            404,
        );

        $post->load('category');

        return view('posts.show', [
            'post' => $post,
        ]);
    }
}
