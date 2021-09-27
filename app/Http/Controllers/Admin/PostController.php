<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Post;
use App\Category;
use App\Tag;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::all();
        $categories = Category::all();
        return view('admin.posts.index', compact('posts', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.posts.create', compact('categories','tags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->tags);

        //validazione dei dati
        $request->validate([
            'title' => 'required|max:60',
            'content' => 'required',
            'image' => 'nullable|image'
        ]);

        // dati
        $data = $request->all();
        // dd($data);

        // nuovo post
        $new_post = new Post();

        $slug = Str::slug($data['title'], '-');

        // duplicato
        $slug_base = $slug;

        $slug_presente = Post::where('slug', $slug)->first();

        $contatore = 1;
        while ($slug_presente) {
            $slug = $slug_base . '-' . $contatore;

            $slug_presente = Post::where('slug', $slug)->first();

            $contatore++;
        }

        $new_post->slug = $slug;

        if(array_key_exists('image', $data)){
            //path
            $cover_path = Storage::put('covers', $data['image']);

            $data['cover'] = $cover_path;
        }

        $new_post->fill($data);

        // salvare
        $new_post->save();

        $new_post->tags()->attach($request->tags);

        return redirect()->route('admin.posts.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    //collegamento slug
    public function show($slug)
    {
        $post = Post::where('slug',$slug)->first();
        return view('admin.posts.show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.posts.edit', compact('post', 'categories', 'tags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        //validate
        $request->validate([
            'title' => 'required|max:60',
            'content' => 'required',
            'image' => 'nullable|image'
        ]);

        $data = $request->all();

        if($data['title'] != $post->title){

            $slug = Str::slug($data['title'], '-'); 
            $slug_base = $slug; 

            $slug_presente = Post::where('slug', $slug)->first();

            $contatore = 1;
            while($slug_presente){

                $slug = $slug_base . '-' . $contatore;

                $slug_presente = Post::where('slug', $slug)->first();

                $contatore++;
            }

            $data['slug'] = $slug;
        }

        if(array_key_exists('image', $data)){
            $cover_path = Storage::put('covers', $data['image']);
            
            Storage::delete($post->cover);
            $data['cover'] = $cover_path;
        }

        $post->update($data);
        $post->tags()->sync($request->tags);

        return redirect()->route('admin.posts.index')->with('updated', 'Hai modificato con successo l\'elemento ' . $post->id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        Storage::delete($post->cover);
        $post->delete();
        $post->tags()->detach();
        
        return redirect()->route('admin.posts.index');
    }
}
