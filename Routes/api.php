<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$route_prefix = config('post.route_prefix', 'manager');
$route_url_prefix = $route_prefix ? $route_prefix . '/' : '';
$route_name_prefix = $route_prefix ? $route_prefix . '.' : '';

Route::prefix("{$route_url_prefix}post")->name("api.{$route_name_prefix}post.")->group(function () {
	Route::post('/post', "PostController@edit")->name('edit');
	Route::get('/post', 'PostController@items')->name('items');
	Route::get('/post/homology', 'PostController@homologyItems')->name('homology');
	Route::get('/post/{id}', 'PostController@item')->where('id', '[0-9]+')->name('item');
	Route::post('/post/delete', 'PostController@delete')->name('delete');
});
