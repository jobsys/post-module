<?php

namespace Modules\Post\Http\Controllers;

use App\Http\Controllers\BaseManagerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Modules\Post\Entities\Post;
use Modules\Post\Entities\PostGroup;
use Modules\Starter\Emnus\State;
use Modules\Starter\Entities\Category;

class PostController extends BaseManagerController
{
	public function pagePost(Request $request)
	{

		$group = $request->input('group', 'news');

		$category_options = Category::where('module', 'post')->where('group', $group)->where('homology_id', 0)->get(['id', 'name'])
			->map(function ($item) {
				return ['label' => $item->name, 'value' => $item->id];
			})->toArray();

		return Inertia::render('PagePost@Post', [
			'categoryOptions' => $category_options,
		]);
	}

	public function items(Request $request)
	{

		$pagination = Post::filterable()->with(['category:id,name'])->where('homology_id', 0)->orderByDesc('sort_order')
			->select(['id', 'category_id', 'title', 'slug', 'brief', 'cover', 'lang', 'is_top', 'is_active', 'sort_order', 'views_count', 'started_at', 'ended_at', 'created_at'])
			->paginate();

		log_access('查看新闻公告列表');


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

		log_access('查看新闻公告详情', $id);

		return $this->json($item);
	}

	public function edit(Request $request)
	{

		list($input, $error) = land_form_validate(
			$request->only('id', 'cover', 'title', 'content', 'brief', 'category_id', 'homology_id', 'lang',
				'slug', 'attachments', 'started_at', 'ended_at', 'sort_order', 'is_top', 'is_active'),
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

			$result = Post::where('id', $input['id'])->update($input);
			//由于支持多语言，在更新主体时，需要同时更新其他语言的SLUG
			if ($input['lang'] === App::getLocale()) {
				Post::where('homology_id', $input['id'])->update(['slug' => $input['slug']]);
			}
		} else {
			if (!isset($input['slug']) || !$input['slug']) {
				if (!isset($input['homology_id']) || !$input['homology_id']) {
					$slug = mb_strlen($input['title']) > 10 ? pinyin_abbr($input['title']) : implode("-", pinyin($input['title']));
					$index = 1;
					while (!land_is_model_unique(['slug' => $slug], Post::class, 'slug', true, [
						'homology_id' => 0
					])) {
						$slug = $slug . $index;
						$index += 1;
					}
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
