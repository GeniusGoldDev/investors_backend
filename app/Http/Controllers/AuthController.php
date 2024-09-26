<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\RegistrationEmail;
use App\Mail\RegistrationEmail as MailRegistrationEmail;
use App\Models\Investor;
use App\Models\LoggedInUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Requests\UpdatePasswordRequest;
use App\Mail\SendMailInvestors;
use App\Models\PasswordReset;
use App\Models\SellProperty;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'register_marketer', 'sendPasswordResetEmail', 'change_password', 'passwordResetProcess', 'complete_profile', 'getAUser']]);
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone' => 'required|numeric|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|confirmed|string|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 405);
        }
        $user = new Investor;
        $user->fname = $request->fname;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->country = $request->country;
        $user->password = Hash::make($request->password);
        $user->save();


        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // $user->delete();

        return $this->respondWithToken($token);
    }
    public function register_marketer(Request $request)
    {
        if ($request->hasFile('avatar')) {
            $image = $request->file('avatar');
            $originalName = $image->getClientOriginalName();
            $path = Storage::putFileAs('public/images', $image,$originalName);
            $file = storage_path('app/'.$path);
            // $file = Storage::url('app/'.$path);
            $url = asset('storage/images/'.$originalName);
        }
        $validator = Validator::make($request->all(), [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone' => 'required|numeric|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|confirmed|string|min:8',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 405);
        }
        $user = new Investor;
        $user->fname = $request->fname;
        $user->avatar = $url ? $url : null;
        $user->lname = $request->lname;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->country = $request->country;
        $user->user_type = 'marketer';
        $user->password = Hash::make($request->password);
        $user->save();


        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // $user->delete();

        return $this->respondWithToken($token);
    }

    public function get_marketer(Request $request)
    {
        $marketers = Investor::where('user_type', 'marketer')->get();
        return $marketers;
    }

    public function getMarketerProperty($property_id)
    {
        $propertywithmarketers = SellProperty::join('users', 'users.id', 'marketer_id')
            ->where(['approved_request_id' => $property_id, 'user_id' => Auth::user()->id])
            ->get()->toArray();
        // trying to make marketer id the main id
        foreach ($propertywithmarketers as $key => $value) {
            $propertywithmarketers[$key]['id'] = $value['marketer_id'];
        }
        return response()->json(['propertywithmarketers' => $propertywithmarketers], 200);
    }

    public function loginAs($user_id)
    {

        if (!$user_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Investor::where('id', $user_id)->first();

        $tok = JWTAuth::fromUser($user);
        return $this->respondWithToken2($tok, $user);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }




        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token)
    {
        $user = Investor::where('id', auth()->user()->id)->first();
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'data' => $user
        ]);
    }

    protected function respondWithToken2($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'data' => $user
        ]);
    }


    public function sendPasswordResetEmail(Request $request)
    {
        // If email does not exist
        if (!$this->validEmail($request->email)) {
            return response()->json([
                'message' => 'Email does not exist.'
            ], Response::HTTP_NOT_FOUND);
        } else {
            // If email exists
            $this->sendMail($request->email);
            return response()->json([
                'message' => 'Check your inbox, we have sent a link to reset email.'
            ], Response::HTTP_OK);
        }
    }
    public function sendMail($email)
    {
        $token = $this->generateToken($email);
        Mail::to($email)->send(new SendMailInvestors($token));
    }
    public function validEmail($email)
    {
        return !!Investor::where('email', $email)->first();
    }

    public function passwordResetProcess(UpdatePasswordRequest $request)
    {
        return $this->updatePasswordRow($request)->count() > 0 ? $this->resetPassword($request) : $this->tokenNotFoundError();
    }
    public function getEmailUsingToken(Request $request)
    {
        $data = null;
        if ($request->has("token")) {
            $data = PasswordReset::where('token', $request->token)->first()->email;
        }
        return response()->json($data ?? 0, 200);
    }
    private function updatePasswordRow($request)
    {
        return PasswordReset::where([
            'email' => $request->email,
            'token' => $request->token
        ]);
    }
    // Reset password
    private function resetPassword($request)
    {
        // find email
        $userData = Investor::whereEmail($request->email)->first();
        // update password
        $userData->update([
            'password' => bcrypt($request->password)
        ]);
        // remove verification data from db
        $this->updatePasswordRow($request)->delete();
        // reset password response
        return response()->json([
            'data' => 'Password has been updated.'
        ], Response::HTTP_CREATED);
    }

    public function change_password(Request $request)
    {

        $user = Investor::where('id', $request->input('id'))->first();
        $hashedPassword = $user->password;


        $request->validate([
            'oldpassword' => ['required'],
            'password' => ['required'],
            'confirm' => ['required'],
        ]);

        if (Hash::check($request->oldpassword, $hashedPassword) && $request->confirm == $request->password) {
            $response = [];
            $user->update([
                'password' => bcrypt($request->password),
            ]);
            array_push($response, ['success' => 'Password changed successfully']);

            return response()->json($response, 280);
        } else {
            $password_error = [];
            if ($request->confirm != $request->password) {
                array_push($password_error, ['error' => 'New password does not match!']);
            }
            if (Hash::check($request->oldpassword, $hashedPassword) == false) {
                array_push($password_error, ['error' => 'You entered an incorrect old password']);
            }
            return response()->json($password_error, 500);
        }
    }
    public function generateToken($email)
    {
        $isOtherToken = PasswordReset::where('email', $email)->first();
        if ($isOtherToken) {
            return url(request()->header('origin')) . '/reset/' . $isOtherToken->token;
        }
        $token = Str::random(80);;
        $this->storeToken($token, $email);
        return url(request()->header('origin')) . '/reset/' . $token;
    }
    public function storeToken($token, $email)
    {
        PasswordReset::create([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
    }
    // Token not found response
    private function tokenNotFoundError()
    {
        return response()->json([
            'error' => 'Either your email or token is wrong.'
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function complete_profile()
    {
        if (request()->hasFile('avatar')) {
            $image = request()->file('avatar');
            $originalName = $image->getClientOriginalName();
            $path = Storage::putFileAs('public/images', $image,$originalName);
            $file = storage_path('app/'.$path);
            // $file = Storage::url('app/'.$path);
            $url = asset('storage/images/'.$originalName);
        }
        try {
            $update_user = Investor::where('id', request()->id)->update([
                'fname' => request()->fname,
                'lname' => request()->lname,
                'mname' => request()->mname,
                'gender' => request()->gender,
                'avatar' => $url ? $url : null,
                'email' => request()->email,
                'phone' => request()->phone,
                'country' => request()->country
            ]);
            return response()->json(['message' => 'Profile updated successfully'], 200);

            //code...
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Unable to update profile, please try again later', $th->getMessage()], 500);

            //throw $th;
        }
    }

    public function getAUser()
    {

        return Investor::where('id', request()->filters)->first();
    }
}
