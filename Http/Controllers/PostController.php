<?php

namespace Modules\Post\Http\Controllers;

use App\Http\Controllers\BaseManagerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Modules\Post\Entities\Post;
use Modules\Starter\Emnus\State;
use Modules\Starter\Entities\Category;

class PostController extends BaseManagerController
{
    public function pagePost(Request $request)
    {

        $group = $request->input('group', 'news');

        $category_options = Category::whereNull('parent_id')->where('module', 'post')->where('group', $group)->orderBy('sort_order', 'DESC')->get();
        $category_options = land_get_closure_tree($category_options);

        return Inertia::render('PagePost@Post', [
            'categoryOptions' => $category_options,
        ]);
    }

    public function items(Request $request, $group = 'news')
    {
        $pagination = Post::filterable()->withWhereHas('category', function ($query) use ($group) {
            return $query->where('group', $group)->select('id', 'name');
        })->where('homology_id', 0)->orderByDesc('sort_order')
            ->orderByDesc('created_at')
            ->select(['id', 'category_id', 'title', 'slug', 'brief', 'cover', 'lang', 'published_at', 'is_top', 'is_active', 'sort_order', 'views_count', 'started_at', 'ended_at', 'created_at'])
            ->paginate();

        return $this->json($pagination);
    }

    public function homologyItems(Request $request)
    {
        $id = $request->input('id');

        $items = Post::where('homology_id', $id)->get();

        return $this->json($items);
    }


    public function item(Request $request, $id)
    {
        $item = Post::where('id', $id)->first();

        if (!$item) {
            return $this->json(null, State::NOT_FOUND);
        }

        log_access("查看{$item->getModelName()}", $item);

        return $this->json($item);
    }

    public function edit(Request $request)
    {

        list($input, $error) = land_form_validate(
            $request->only('id', 'cover', 'title', 'content', 'brief', 'category_id', 'homology_id', 'lang',
                'slug', 'attachments', 'published_at', 'started_at', 'ended_at', 'sort_order', 'is_top', 'is_active'),
            [
                'category_id' => 'bail|required|numeric',
                'title' => 'bail|required|string',
                'content' => 'bail|required|string',
            ],
            [
                'category_id' => '分类',
                'title' => '标题',
                'content' => '内容',
            ]
        );

        if ($error) {
            return $this->message($error);
        }


        if (isset($input['published_at']) && $input['published_at']) {
            $input['published_at'] = land_predict_date_time($input['published_at'], 'date');
        }

        if (isset($input['started_at']) && $input['started_at']) {
            $input['started_at'] = land_predict_date_time($input['started_at'], 'date');
        }

        if (isset($input['ended_at']) && $input['ended_at']) {
            $input['ended_at'] = land_predict_date_time($input['ended_at'], 'date');
        }

        if (!empty($input['started_at']) && !empty($input['ended_at']) && $input['ended_at']->isBefore($input['started_at'])) {
            return $this->message('开始时间不能大于结束时间');
        }

        $input['homology_id'] = $input['homology_id'] ?? 0;
        $input['lang'] = $input['lang'] ?? App::getLocale();

        if (isset($input['id']) && $input['id']) {
            if ($input['homology_id'] === 0) {
                $unique = land_is_model_unique($input, Post::class, 'slug', true, ['homology_id' => 0]);
                if (!$unique) {
                    return $this->message('该分类标识已经存在');
                }
            }

            Post::where('id', $input['id'])->update($input);
            //由于支持多语言，在更新主体时，需要同时更新其他语言的SLUG
            if ($input['lang'] === App::getLocale()) {
                Post::where('homology_id', $input['id'])->update(['slug' => $input['slug']]);
            }
            $result = Post::find($input['id']);
        } else {
            if (!isset($input['slug']) || !$input['slug']) {
                if (!isset($input['homology_id']) || !$input['homology_id']) {
                    $slug = land_slug($input['title'], Post::class, ['homology_id' => 0]);
                    $input['slug'] = $slug;
                } else {
                    $homology = Post::find($input['homology_id']);
                    if ($homology) {
                        $input['slug'] = $homology->slug;
                    }
                }
            }


            $input['creator_id'] = $this->login_user_id;
            $result = Post::updateOrCreate(['slug' => $input['slug'], 'lang' => $input['lang']], $input);
        }

        return $this->json(null, $result ? State::SUCCESS : State::FAIL);
    }

    public function delete(Request $request)
    {
        $id = $request->input('id');
        $item = Post::where('id', $id)->first();

        if (!$item) {
            return $this->json(null, State::NOT_ALLOWED);
        }

        $result = $item->delete();

        return $this->json(null, $result ? State::SUCCESS : State::FAIL);
    }


}
