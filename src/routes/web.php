<?php
/**
 * Created by PhpStorm.
 * User: nrt
 * Date: 31/01/2019
 * Time: 16:25
 */

Route::get('/testvendor', function(){
    dd("Vendor test!");
});

Route::group(['middleware' => 'auth'], function(){         // Al de pagina's die daartussen staan, moeten door een login
    Route::group(['prefix' => 'beheer'], function(){
        Route::post('/fotos/upload', 'UploadController@uploadAction');
        Route::get('/test', 'UploadController@test');
//        Route::get('/importeren', 'GoogleSheetMgtController@importProgrammatieIndex');
        Route::get('/proggenerator', 'GoogleSheetMgtController@proggeneratorWizard');
        Route::get('/sheets', 'GoogleSheetMgtController@index2')->name('sheets.index2'); // Programmatie Generator
    });

//-- SECURED API CALLS
    Route::group(['prefix' => 'api'], function(){
        Route::group(['prefix' => 'beheer'], function() {
            Route::get('/jaargangen/{id}', 'SheetMgtController@verify');
            Route::post('importsheet', 'SheetMgtController@import');
            Route::post('createjsonfiles','programmatieController@createJsonFiles');

            Route::resource('sheets', 'SheetMgtController');
            Route::resource('verkooppunten', 'VerkooppuntenController');
        });
    });
//-- END SECURED API CALLS
}); // END PASSWORD PROTECTED