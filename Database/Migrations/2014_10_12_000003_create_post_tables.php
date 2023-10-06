<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
	public function up()
	{
		/**
		 * 文章
		 */
		Schema::create('posts', function (Blueprint $table) {
			$table->id();
			$table->integer('category_id')->index()->comment('所属分组');
			$table->string("lang")->default('zh_CN')->nullable()->index()->comment('语言');
			$table->integer('homology_id')->default(0)->nullable()->index()->comment('关联ID');
			$table->string('slug')->index()->nullable()->comment('别名');
			$table->integer('creator_id')->index()->comment('创建者');
			$table->string('title')->comment('标题');
			$table->text('brief')->nullable()->comment('摘要');
			$table->longText('content')->nullable()->comment('内容');
			$table->json('cover')->nullable()->comment('封面');
			$table->json('attachments')->nullable()->comment('附件');
			$table->dateTime('started_at')->nullable()->comment('开始时间');
			$table->dateTime('ended_at')->nullable()->comment('结束时间');
			$table->integer('views_count')->default(0)->comment('点击数');
			$table->integer('sort_order')->default(0)->comment('排序，数字越大越靠前');
			$table->boolean('is_top')->default(false)->comment('是否置顶');
			$table->boolean('is_draft')->default(false)->comment('是否为草稿');
			$table->boolean('is_active')->default(true)->comment('是否激活');
			$table->timestamps();
		});

	}

	public function down()
	{
		Schema::dropIfExists('posts');
	}
};
