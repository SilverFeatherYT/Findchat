<?php

use Illuminate\Support\Facades\Route;

Route::group(['as' => 'api.', 'namespace' => 'Api' , 'middleware' => ['cors']], function () {

    Route::get('/chat/recent-chat', 'ChatController@getRecentChat');
    Route::get('/chat/messages', 'ChatController@getMessages');
    Route::post('/chat/send', 'ChatController@sendMessage');


    Route::get('/search-users', 'ChatController@searchUsers');


    Route::post('/count-unread-messages', 'ChatController@countUnreadMessages');
    Route::post('/mark-messages-as-read', 'ChatController@markMessagesAsRead');


    Route::get('/blacklist', 'ChatController@getBlackList');
    Route::post('/add-blacklist', 'ChatController@addBlacklist');
    Route::post('/delete-blacklist', 'ChatController@deleteBlacklist');
    Route::get('/check-blacklist', 'ChatController@checkBlackList');


    Route::post('/delete/chat', 'ChatController@deleteChat');


    Route::get('/chat-response-rate','ChatController@countResponseRate');


    Route::post('/media', 'ChatController@storeMedia')->name('storeMedia');

});
