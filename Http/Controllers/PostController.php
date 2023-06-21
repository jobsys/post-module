<?php

namespace Modules\Post\Http\Controllers;

use App\Http\Controllers\BaseManagerController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Post\Entities\Post;
use Modules\Post\Entities\PostGroup;
use Modules\Starter\Emnus\State;

class PostController extends BaseManagerController
{
    /**
     * Display a listing of the resource.
     */
    public function pagePost()
    {

        $group_options = PostGroup::get(['id', 'display_name'])->map(function (PostGroup $item) {
            return [
                'value' => $item->id,
                'label' => $item->display_name,
            ];
        })->toArray();

        return Inertia::render('PagePost@Post', [
            'groupOptions' => $group_options,
        ]);
    }


    public function groupItems(Request $request)
    {
        $display_name = $request->input('display_name');

        $pagination = PostGroup::withCount(['posts'])
            ->when($display_name, function ($query) use ($display_name) {
                return $query->where('display_name', 'like', '%' . $display_name . '%');
            })
            ->paginate();

        return $this->json($pagination);
    }

    public function groupEdit(Request $request)
    {

        list($input, $error) = land_form_validate(
            $request->only('id', 'display_name'),
            [
                'display_name' => 'bail|required|string',
            ],
            [
                'display_name' => '分类名称',
            ]
        );

        if ($error) {
            return $this->message($error);
        }

        $unique = land_is_model_unique($input, PostGroup::class, 'display_name', true);

        if (!$unique) {
            return $this->message('该分类已经存在');
        }


        if (isset($input['id']) && $input['id']) {
            $result = PostGroup::where('id', $input['id'])->update($input);
        } else {
            $name = pinyin_abbr($input['display_name']);

            $index = 1;
            while (!land_is_model_unique(['name' => $name], PostGroup::class, 'name', true)) {
                $name = $name . $index;
                $index += 1;
            }
            $input['name'] = $name;
            $result = PostGroup::create($input);
        }


        log_access(isset($input['id']) && $input['id'] ? '编辑新闻公告分类' : '新建新闻公告分类', $input['id'] ?? $result->id);

        return $this->json(null, $result ? State::SUCCESS : State::FAIL);
    }

    public function groupDelete(Request $request)
    {
        $id = $request->input('id');
        $item = PostGroup::where('id', $id)->first();

        if (!$item) {
            return $this->json(null, State::NOT_ALLOWED);
        }

        $is_empty = Post::where('post_group_id', $id)->count() == 0;

        if (!$is_empty) {
            return $this->json(null, '该分类下还有内容，删除失败');
        }

        $result = $item->delete();

        log_access('删除新闻公告分类', $id);
        return $this->json(null, $result ? State::SUCCESS : State::FAIL);
    }

    public function items(Request $request)
    {

        $post_group_id = $request->input('post_group_id', false);
        $title = $request->input('title', false);

        $pagination = Post::with(['group:id,display_name'])->when($post_group_id, function ($query, $post_group_id) {
            $query->where('post_group_id', $post_group_id);
        })->when($title, function ($query, $title) {
            $query->where('title', 'like', "%{$title}%");
        })->orderByDesc('sort_order')
            ->select(['id', 'post_group_id', 'title', 'brief', 'cover', 'is_top', 'is_active', 'sort_order', 'views_count', 'started_at', 'ended_at', 'created_at'])
            ->paginate();

        log_access('查看新闻公告列表');


        return $this->json($pagination);
    }

    public function item(Request $request, $id)
    {
        $item = Post::where('id', $id)->first();

        if (!$item) {
            return $this->json(null, State::NOT_FOUND);
        }

        log_access('查看新闻公告详情', $id);

        return $this->json($item);
    }

    public function edit(Request $request)
    {

        list($input, $error) = land_form_validate(
            $request->only('id', 'cover', 'title', 'content', 'brief', 'post_group_id', 'attachments', 'started_at', 'ended_at', 'sort_order', 'is_top', 'is_active'),
            [
                'post_group_id' => 'bail|required|numeric',
                'title' => 'bail|required|string',
                'content' => 'bail|required|string',
            ],
            [
                'post_group_id' => '分类',
                'title' => '标题',
                'content' => '内容',
            ]
        );

        if ($error) {
            return $this->message($error);
        }


        if (isset($input['started_at']) && $input['started_at']) {
            $input['started_at'] = land_predict_date_time($input['started_at'], 'date');
        }

        if (isset($input['ended_at']) && $input['ended_at']) {
            $input['ended_at'] = land_predict_date_time($input['ended_at'], 'date');
        }

        if (!empty($input['started_at']) && !empty($input['ended_at']) && $input['ended_at']->isBefore($input['started_at'])) {
            return $this->message('项目开始时间不能大于结束时间');
        }

        if (isset($input['id']) && $input['id']) {
            $result = Post::where('id', $input['id'])->update($input);
        } else {
            $input['creator_id'] = $this->login_user_id;
            $result = Post::create($input);
        }
        log_access(isset($input['id']) && $input['id'] ? '编辑新闻公告' : '新建新闻公告', $input['id'] ?? $result->id);

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

        log_access('删除新闻公告', $id);
        return $this->json(null, $result ? State::SUCCESS : State::FAIL);
    }


}
