<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api', 'prefix' => 'auth/investors'], function ($router) {
    Route::post('/reset-password-request', [AuthController::class, 'sendPasswordResetEmail']);

    Route::post('register', [AuthController::class, 'register']);
    Route::post('register/marketer', [AuthController::class, 'register_marketer']);
    Route::get('/getmarketer', [AuthController::class, 'get_marketer']);
    Route::post('/getmarketer-property/{property_id}', [AuthController::class, 'getMarketerProperty']);
    Route::post('user_type', [AuthController::class, 'auth_user_type']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('login-as/{user_id}', [AuthController::class, 'loginAs']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('/reset-password', [AuthController::class, 'passwordResetProcess']);
    Route::post('/email-using-token', [AuthController::class, 'getEmailUsingToken']);
    Route::post('/change-password', [AuthController::class, 'change_password']);
    Route::post('complete-profile', [AuthController::class, 'complete_profile'])->name('complete_profile');
    Route::post('/getUserData/all', [AuthController::class, 'getAUser'])->name('getAUser2');
    
});

Route::group(['middleware' => 'api'], function ($router) {

    
     Route::group(['prefix' => 'investors'], function ($router) {
        Route::group(['prefix' => 'properties'], function ($router) {
            Route::post('all', [MainController::class, 'all_properties_user']);
            Route::post('notify_new_property', [MainController::class, 'notify_new_property']);
            
        
        });
        Route::group(['prefix' => 'requests'], function ($router) {
            Route::post('make', [MainController::class, 'make_request']);
            Route::post('dashboard_analytics', [MainController::class, 'dashboard_analytics']);
            Route::post('marketer_dashboard_analytics', [MainController::class, 'marketer_dashboard_analytics']);
            Route::post('get', [MainController::class, 'all_request']);
            Route::post('reply_message', [MainController::class, 'reply_message_user']);
            Route::post('approved_request', [MainController::class, 'all_approved_request_for_user']);
            Route::post('completed_request', [MainController::class, 'completed_request']);
            Route::post('delete_request', [MainController::class, 'deleteRequest']);

            Route::post('get_user_conversations', [MainController::class, 'get_conversations_user']);

         

            Route::post('get_request_transactions', [MainController::class, 'get_transactions_for_user']);

            
        });




        Route::group(['prefix' => 'requests'], function ($router) {
            Route::group(['prefix' => 'admin'], function ($router) {
                Route::post('all', [MainController::class, 'all_request_for_admin']);
                Route::post('get_conversations', [MainController::class, 'get_conversations']);
                Route::post('reply_message', [MainController::class, 'reply_message']);
                Route::post('approve_request', [MainController::class, 'approve_request']);
                Route::post('transaction', [MainController::class, 'transaction']);
                Route::post('all_approved_request', [MainController::class, 'all_approved_request']);
                Route::post('get_transactions', [MainController::class, 'get_transactions']);
                Route::post('update_transaction_breakdown', [MainController::class, 'update_transaction_breakdown']);
                Route::post('update_actual_amount', [MainController::class, 'update_actual_amount']);
                Route::delete('delete_transaction_breakdown/{id}', [MainController::class, 'delete_transaction_breakdown']);
                Route::post('record_transactions', [MainController::class, 'record_transactions']);
                Route::post('change_contract_status', [MainController::class, 'change_contract_status']);
                Route::post('record_receipt', [MainController::class, 'record_receipt']);
                
            });
            
        });
        Route::group(['prefix' => 'properties'], function ($router) {
            Route::group(['prefix' => 'admin'], function ($router) {
                Route::post('all', [MainController::class, 'all_properties']);
                Route::post('add', [MainController::class, 'add_property']);
                Route::post('update/{id}', [MainController::class, 'update']);
                Route::post('property_link/{id}', [MainController::class, 'property_link']);
                Route::post('change_image', [MainController::class, 'change_image']);
                Route::post('remove_single_image', [MainController::class, 'remove_single_image']);
                Route::delete('/{id}', [MainController::class, 'destroy']);
                Route::put('/toggle-status/{id}', [MainController::class, 'toggle_status']);
                Route::post('/assign_key', [MainController::class, 'assign_key']);
                Route::post('/assign_deed_of_assignment', [MainController::class, 'assign_deed_of_assignment']);
                Route::post('allocate_property/{id}', [MainController::class, 'allocate']);
                Route::post('survey_plan/{id}', [MainController::class, 'survey_plan']);
                Route::post('assignment_type/{id}', [MainController::class, 'assignment_type']);

    
                
                
            });
            Route::post('attach-marketers/{property_id}', [MainController::class, 'attachMarkerters']);
            Route::post('delete-marketers/{approve_request_id}', [MainController::class, 'deleteMarkerters']);
            Route::post('get-marketers-property', [MainController::class, 'getMarketersProperty']);
            Route::post('manage-marketers-property', [MainController::class, 'manageMarketers']);
            Route::post('toggle-status/{approved_request_id}/{marketer_id}', [MainController::class, 'ChangeStatus']);
        });

        Route::get('dashboard_stats', [MainController::class, 'dashboard_stats']);
        Route::post('/users/fetch', [MainController::class, 'all_users']);
        Route::post('/users/analytics', [MainController::class, 'analytics']);
        Route::post('/users/edit', [MainController::class, 'update_user_details']);
        Route::delete('/users/delete/{id}', [MainController::class, 'deleteUserDetails']);
        Route::get('/users/reset-password/{id}', [MainController::class, 'reset_user_password'])->name('reset-password');



        
    });
    // Investors admin
   
    
    

});
