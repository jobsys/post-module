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


use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Starter\Entities\BaseModel;

class PostGroup extends BaseModel
{

    protected $appends = [
        'created_at_datetime'
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
