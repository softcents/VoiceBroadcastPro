<?php

Route::get('auth/login', function (){
    return redirect('login');
})->name('login');
