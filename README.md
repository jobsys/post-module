# **Post** 新闻资讯模块

该模块提供了基础的新闻资讯分类，以及的新闻资讯 CRUD。

## 模块安装

```bash
composer require jobsys/post-module
```

### 依赖

- PHP 依赖 （无）

- JS 依赖 （无）

### 配置

#### 模块配置

```php
"Post" => [
    "route_prefix" => "manager",                                                    // 路由前缀
]
```

## 模块功能

### 新闻资讯功能

略

#### 开发规范

只是一些简单的 CRUD，没有什么特别的开发规范。

## 模块代码

### 数据表

```bash
2014_10_12_000003_create_post_tables                     # 新闻资讯数据表
```

### 数据模型/Scope

```bash
Modules\Post\Entities\PostGroup                # 新闻资讯分类
Modules\Post\Entities\Post                     # 新闻资讯
```

### Controller

```bash
Modules\Post\Http\Controllers\PostController        # CRUD API
```

### UI

#### PC 端页面

```bash
web/PagePost.vue                        # 新闻资讯管理页面
```
