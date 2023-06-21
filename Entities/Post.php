<?php
/*======================================================================
*
*   Copyright (C) 2014-2021 广州啪嗒信息科技有限公司
*   All rights reserved
*
*   Project     : second-classroom
*   Author      : sinceow
*   Time        : 2021/2/9
*   Description :
*
*   Powered by http://www.padakeji.com
=========================================================================*/

namespace Modules\Post\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Starter\Entities\BaseModel;

class Post extends BaseModel
{


    protected $casts = [
        'cover' => 'array',
        'attachments' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_top' => 'boolean',
        'is_draft' => 'boolean',
        'is_active' => 'boolean'
    ];

    protected $appends = [
        'started_at_datetime',
        'ended_at_datetime',
        'created_at_datetime'
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PostGroup::class, 'post_group_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }
}
