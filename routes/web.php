<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('items/fetch', 'ItemController@fetch')->name('items.fetch');
Route::post('items/rate', 'ItemController@rate')->name('items.rate');
Route::resource('items', 'ItemController');
