 <?php

    use Illuminate\Support\Facades\Route;
    use App\Http\Controllers\AuthController;
    use App\Http\Controllers\RegistrationController;
    use App\Http\Controllers\VerificationController;
    use App\Http\Controllers\ForgotPasswordController;
    use App\Http\Controllers\GoogleAuthController;
    use App\Http\Controllers\UserController;

    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me'])->middleware('log.route');

        Route::post('register', [RegistrationController::class, 'register']);
        Route::get('email/verify/{id}', [VerificationController::class, 'verify'])->name('verification.verify');
        Route::get('email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
        Route::post('password/email', [ForgotPasswordController::class, 'forgot']);
        Route::post('password/reset', [ForgotPasswordController::class, 'reset']);

        Route::patch('user/profile', [UserController::class, 'updateProfile']);
        //login
        Route::post('google_login', [GoogleAuthController::class, 'login']);
        // add more routes here
    });
