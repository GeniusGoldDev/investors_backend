<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Request as InvestorsRequest;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponseTrait;
use App\Mail\NotifyMarketer;
use App\Models\ApprovedRequest;
use App\Models\Investor;
use App\Models\InvestorsProperty;
use App\Models\RequestConversation;
use App\Models\RequestTransaction;
use App\Models\Request as ModelRequest;
use App\Models\SellProperty;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


class MainController extends Controller
{
    //
    use ApiResponseTrait;
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function make_request(Request $request)
    {
        $request->validate([
            'name' => 'required',
            // 'name' => 'required|string|unique:mysqlOne.requests',

        ]);

        $userRequest = new InvestorsRequest;
        $userRequest->name =  $request->name;
        $userRequest->investor_id =  auth()->user()->id;
        if (!$request->has("pi"))  $userRequest->is_special =  "yes";
        if ($request->has("pi"))  $userRequest->investor_property_id =  $request->pi;
        if ($request->has("sqm_price"))  $userRequest->square_meter_price =  $request->sqm_price;


        $userRequest->save();


        return $this->successResponse('Request saved successfully', $userRequest);
    }

    public function dashboard_analytics()
    {
        $amount_spent = RequestTransaction::where([
            'status' => 'approved',
            'investor_id' => auth()->user()->id
        ])->sum('amount');
        $property_bought = ApprovedRequest::with(['request'])
            ->whereHas('request', function ($q) {
                $q->where('investor_id', auth()->user()->id);
            })
            ->where('status', 'completed')->count();
        $approved_request = InvestorsRequest::where([
            'status' => 'approved',
            'investor_id' => auth()->user()->id


        ])->count();

        $data = ["amount_spent" => $amount_spent, 'property_bought' => $property_bought, 'approved_request' => $approved_request];

        $requests =  ApprovedRequest::with(['request'])

            ->whereHas('request', function ($q) {
                $q->where('investor_id', auth()->user()->id);
            })
            ->withCount([
                'amount_paid as amount_paid' => function ($q) {
                    $q->select(DB::raw("SUM(amount) as amount"))
                        ->where('investor_id', auth()->user()->id);
                }
            ])
            ->get();
        $data["requests"] = $requests;

        return $this->successResponse('Records', $data);
    }

    public function marketer_dashboard_analytics()
    {
        $all_property = SellProperty::where([
            'marketer_id' => auth()->user()->id
        ])->count();
        $property_sold = SellProperty::where(['status' => 'completed', 'marketer_id' => auth()->user()->id])->count();
        $pending = SellProperty::where([
            ['status', '!=', 'completed'],
            ['marketer_id', '=', auth()->user()->id]
        ])->count();
        $data = ["all_property" => $all_property, 'property_sold' => $property_sold, 'pending' => $pending];

        $requests =  ApprovedRequest::with(['request'])

            ->whereHas('request', function ($q) {
                $q->where('investor_id', auth()->user()->id);
            })
            ->withCount([
                'amount_paid as amount_paid' => function ($q) {
                    $q->select(DB::raw("SUM(amount) as amount"))
                        ->where('investor_id', auth()->user()->id);
                }
            ])
            ->get();
        $data["requests"] = $requests;

        return $this->successResponse('Records', $data);
    }

    public function all_request(Request $request)
    {
        return $this->successResponse(
            'Request fetched successfully',
            InvestorsRequest::where('investor_id', auth()->user()->id)
                ->withCount(['request_conversation'])
                ->orderBy('created_at', 'desc')->get()
        );
    }

    public function reply_message_user(Request $request)
    {
        $request->validate([
            'message' => 'required',
            'request_id' => 'required'
        ]);

        $previousCount = RequestConversation::count();
        $getAdminId = Investor::where('user_type', 'admin')->first()->id;
        $create_convo = RequestConversation::create([
            'convo_1' => $request->message,
            'request_id' => $request->request_id,
            'record_order' => (int) $previousCount + 1,
            'convo_1_id' => auth()->user()->id,
            'convo_2_id' => $getAdminId,
        ]);

        broadcast(new MessageSent(Investor::where('id', $getAdminId)->first(), $create_convo->fresh()))->toOthers();

        return $this->successResponse('Request Reply Saved', $create_convo->fresh());
    }

    // ADMIN
    public function all_request_for_admin(Request $request)
    {
        $approved = InvestorsRequest::withCount(['request_conversation as request_conversation'])
            ->with(['property', 'investor'])
            ->withCount(['request_conversation'])
            ->orderBy('created_at', 'desc');
        if ($request->has('status') && $request->status !== null) {
            $approved->where('status', $request->status);
        }
        if ($request->has('search') && $request->search !== null) {
            $approved->whereHas('investor', function ($w) use ($request) {
                $w->where('lname', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('fname', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('mname', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('email', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('phone', 'LIKE', '%' . $request->search . '%');
            });
        }
        return response()->json([
            'data' => $approved->paginate(50)
        ]);
    }

    public function get_conversations(Request $request)
    {
        $request->validate([
            'request_id' => 'required'
        ]);
        return $this->successResponse(
            'Request fetched successfully',
            RequestConversation::where('request_id', $request->request_id)->orderBy('record_order', 'asc')->get()
        );
    }
    public function get_conversations_user(Request $request)
    {
        $request->validate([
            'request_id' => 'required'
        ]);
        return $this->successResponse(
            'Request fetched successfully',
            RequestConversation::where('request_id', $request->request_id)->orderBy('record_order', 'asc')->get()
        );
    }

    public function reply_message(Request $request)
    {
        $request->validate([
            'message' => 'required',
            'request_id' => 'required'
        ]);

        $previousCount = RequestConversation::count();

        $getInvestorsId = InvestorsRequest::where('id', $request->request_id)->first()->investor_id;

        $create_convo = RequestConversation::create([
            'convo_2' => $request->message,
            'request_id' => $request->request_id,
            'record_order' => (int) $previousCount + 1,
            'convo_1_id' => $getInvestorsId,
            'convo_2_id' => auth()->user()->id,
        ]);

        broadcast(new MessageSent(Investor::where('id', $getInvestorsId)->first(), $create_convo->fresh()))->toOthers();


        return $this->successResponse('Request Reply Saved', $create_convo->fresh());
    }

    public function deleteRequest(Request $request)
    {
        $request->validate([
            'request_id' => 'required',
        ]);
        try {
            InvestorsRequest::where('id', $request->request_id)->delete();
            return $this->successResponse('Request Deleted',);
        } catch (\Throwable $th) {
            return $this->failureResponse("Request cannot be deleted", null, 405);
        }
    }



    public function approve_request(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'amount' => 'required',
            'type' => 'required',
            'request_id' => 'required',
            'request_id' => 'required|unique:approved_requests',
        ]);

        $approve_request = new ApprovedRequest;
        $approve_request->name = $request->name;
        $approve_request->amount = $request->amount;
        if ($request->has("custom_price")) {
            $approve_request->amount_used = "custom";
        }
        $approve_request->request_id = $request->request_id;
        $approve_request->type = $request->type;

        if ($request->has("pi"))  $approve_request->investor_property_id = $request->pi;


        $approve_request->save();
        InvestorsRequest::where('id', $approve_request->request_id)->where('id', '!=', null)->update([
            'status' => 'approved'
        ]);
        return $this->successResponse('Request approved successfully',);
    }


    public function transaction(Request $request)
    {
        $request->validate([
            'investor_id' => 'required',
            'approved_request_id' => 'required',
            'amount' => 'required'
        ]);

        $generateTransaction = RequestTransaction::create([
            'investor_id' => $request->investor_id,
            'approved_request_id' => $request->approved_request_id,
            'amount' => $request->amount,
            'status' => 'approved',
        ]);

        return $this->successResponse('Request Transaction generated',);
    }

    public function all_approved_request_for_user(Request $request)
    {
        return $this->successResponse(
            'Approved Request fetched successfully',
            ApprovedRequest::with(['request', 'property'])

                ->whereHas('request', function ($q) {
                    $q->where('investor_id', auth()->user()->id);
                })
                ->withCount([
                    'amount_paid as amount_paid' => function ($q) {
                        $q->select(DB::raw("SUM(amount) as amount"))
                            ->where('investor_id', auth()->user()->id);
                    }
                ])
                ->orderBy('created_at', 'desc')

                ->get()
        );
    }
    public function completed_request()
    {
        $approvedRequest = ApprovedRequest::with(['request'])
            ->whereHas('request', function ($q) {
                $q->where('investor_id', auth()->user()->id);
            })
            ->withCount([
                'amount_paid as amount_paid' => function ($q) {
                    $q->select(DB::raw("SUM(amount) as amount"))
                        ->where('investor_id', auth()->user()->id);
                }
            ])
            ->withCount('attached_marketer')
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse('Approved Request fetched successfully', $approvedRequest);
    }

    public function all_approved_request(Request $request)
    {
        $approved = ApprovedRequest::with(['request', 'property'])
            ->withCount([
                'amount_paid as amount_paid' => function ($q) {
                    $q->select(DB::raw("SUM(amount) as amount"));
                }
            ])
            ->orderBy('created_at', 'desc');
        if ($request->has('status') && $request->status !== null) {
            $approved->where('status', $request->status);
        }
        if ($request->has('search') && $request->search !== null) {
            $approved->whereHas('request.investor', function ($w) use ($request) {
                $w->where('lname', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('fname', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('mname', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('email', 'LIKE', '%' . $request->search . '%');
                $w->orWhere('phone', 'LIKE', '%' . $request->search . '%');
            });
        }
        return response()->json([
            'data' => $approved->paginate(50)
        ]);
    }

    public function get_transactions(Request $request)
    {
        $request->validate([
            'approved_request_id' => 'required'
        ]);


        return $this->successResponse('All request Transactions', RequestTransaction::where('approved_request_id', $request->approved_request_id)
            ->orderBy('created_at', 'desc')
            ->get());
    }

    public function record_transactions(Request $request)
    {
        $request->validate([
            'approved_request_id' => 'required',
            'investor_id' => 'required',
            'amount' => 'required',
        ]);

        RequestTransaction::create([
            'transaction_id' => uniqid('Transaction_ref_'),
            'approved_request_id' => $request->approved_request_id,
            'investor_id' => $request->investor_id,
            'amount' => $request->amount,
            'status' => 'approved'
        ]);
        $approveRequest = ApprovedRequest::where('id', $request->approved_request_id)->first();

        if (RequestTransaction::where([
            'investor_id' => $request->investor_id,
            'approved_request_id' => $request->approved_request_id
        ])->count()) {

            $requestData = InvestorsRequest::where('id', $approveRequest->request_id)->update([
                'status' => 'processing'
            ]);
        }

        if (RequestTransaction::where([
            'investor_id' => $request->investor_id,
            'approved_request_id' => $request->approved_request_id,
            'status' => 'approved'
        ])->sum('amount') >= $approveRequest->amount) {
            $approveRequest->update([
                'status' => 'completed'
            ]);

            $requestData = InvestorsRequest::where('id', $approveRequest->request_id)->update([
                'status' => 'completed'
            ]);
        }

        return $this->successResponse('Request Transaction generated',);
    }

    public function record_receipt(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $record = RequestTransaction::where('id', $request->id)->update([
            'is_reciept_received' => 'yes'
        ]);
        if ($record) return $this->successResponse('Receipt recorded');
        return $this->failureResponse("Receipt cannot be recorded", null, 405);
    }

    public function update_transaction_breakdown(Request $request)
    {
        $request->validate([
            'amount' => 'required',
        ]);
        $old = RequestTransaction::where('id', $request->id)->first();
        $update = RequestTransaction::where('id', $request->id)->update([
            'amount' => $request->amount
        ]);
        if ($update) {
            $lname = auth()->user()->lname;
            $fname = auth()->user()->fname;
            $email = auth()->user()->email;
            $details =  $lname . ' ' . $fname . ' ' . $email . ' Updated Payment details';
            activity()
                ->withProperties(['old_value' => $old->amount, 'new_amount' => $request->amount])
                ->log($details);
            return $this->successResponse('Payment Updated');
        } else {
            return $this->failureResponse("Payment cannot be updated", null, 405);
        }
    }
    public function update_actual_amount(Request $request)
    {
        $request->validate([
            'amount' => 'required',
        ]);
        $old = ApprovedRequest::where('id', $request->id)->first();
        $update = ApprovedRequest::where('id', $request->id)->update([
            'amount' => $request->amount
        ]);
        if ($update) {
            $lname = auth()->user()->lname;
            $fname = auth()->user()->fname;
            $email = auth()->user()->email;
            $details =  $lname . ' ' . $fname . ' ' . $email . ' Updated Payment details';
            activity()
                ->withProperties(['old_value' => $old->amount, 'new_amount' => $request->amount])
                ->log($details);
            return $this->successResponse('Payment Updated');
        } else {
            return $this->failureResponse("Payment cannot be updated", null, 405);
        }
    }
    public function delete_transaction_breakdown(Request $request, $id)
    {
        $old = RequestTransaction::where('id', $id)->first();
        $delete = RequestTransaction::where('id', $id)->delete();
        if ($delete) {
            $lname = auth()->user()->lname;
            $fname = auth()->user()->fname;
            $email = auth()->user()->email;
            $details =  $lname . ' ' . $fname . ' ' . $email . ' Deleted Payment details';
            activity()
                ->withProperties(['old_value' => $old->amount, 'new_amount' => '0'])
                ->log($details);
            return $this->successResponse('Payment Deleted');
        } else {
            return $this->failureResponse("Payment cannot be deleted", null, 405);
        }
    }
    public function change_contract_status(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required',

        ]);

        $record = ApprovedRequest::where('id', $request->id)->update([
            'contract_recieved' => $request->status
        ]);
        if ($record) return $this->successResponse('Contract status changed');
        return $this->failureResponse("Contract status cannot be changed", null, 405);
    }
    public function get_transactions_for_user(Request $request)
    {
        $request->validate([
            'approved_request_id' => 'required'
        ]);


        return $this->successResponse('All request Transactions', RequestTransaction::where('investor_id', auth()->user()->id)
            ->where('approved_request_id', $request->approved_request_id)
            ->orderBy('created_at', 'desc')
            ->get());
    }

    public function all_properties_user(Request $request)
    {

        $carbonDate = Carbon::now();
        $data = InvestorsProperty::orderBy('created_at', 'desc')
            ->select("*", DB::raw("
            IF(updated_at > NOW() - INTERVAL 15 DAY, 1, null) as new_update
        "));
        $data->where('status', 'active');

        return $this->successResponse('All Properties', $data->get());
    }
    public function notify_new_property()
    {
        $getLastRecord = InvestorsProperty::orderby('created_at', 'desc')
            ->whereRaw('created_at > NOW() - INTERVAL 15 DAY')->first();
        return $this->successResponse('Newly created', $getLastRecord);
    }
    public function all_properties(Request $request)
    {


        $data = InvestorsProperty::orderBy('created_at', 'desc');
        if ($request->has('status') && $request->status !== null) {
            $data->where('status', $request->status);
        }
        return $this->successResponse('All Properties', $data->get());
    }

    public function test_pusher()
    {
        broadcast(new MessageSent(auth()->user(), "Hello"))->toOthers();
        return "Done";
    }

    public function add_property(Request $request)
    {

        $request->validate([
            'name' => 'required',

            'location' => 'required',
            'description' => 'required',
            'status' => 'required|string',
            'type' => 'required|string',
            'squareMeters' => 'required',
        ]);


        // Create 
        $ext = [];
        $count = 0;
        if ($request->has("image")) {
            $validExts = ["jpg", "jpeg", "png", "mp4", "svg"];
            foreach ($request->file('image') as  $value) {

                $ext[] = $value->extension();
                if (!in_array($value->extension(), $validExts)) {
                    $count++;
                }
            };
            if ($count > 0) return $this->failureResponse("Unable to add property", 'Some of the file extensions are not valid. Please upload a valid Image Extension');
        }

        $create = new InvestorsProperty;
        $create->name = $request->name;
        // $create->amount =$request->amount;
        $create->location = $request->location;
        $create->description = $request->description;
        $create->status = $request->status;
        $create->type = $request->type;
        $create->square_meters_info = $request->squareMeters;
        if ($request->has("property_link") && !is_null($request->property_link))  $create->property_link = $request->property_link;
        if ($request->has("video_link") && !is_null($request->video_link))  $create->video_link = $request->video_link;
        $create->save();
        if ($request->has("image")) {
            $images = [];
            $filenames = [];
            foreach (request()->file('image') as  $value) {
                $image = time() . '_' . $value->getClientOriginalName();
                $path = $value->storeAs('public/images', $image);

                $path = url('/') . '/storage/images/' . $image;

                $images[] = ['image' => $path];
                $filenames[] = ['filename' => $image];
            };

            $add_mproperty_img = InvestorsProperty::where('id', $create->id)->update([
                'image' => json_encode($images),
                'filename' => json_encode($filenames)
            ]);

            if ($add_mproperty_img) {
                return $this->successResponse("Property successfully saved");
            }
            return $this->failureResponse("Unable to save property image", null, 500);
        }
        return $this->successResponse("Property successfully saved");
    }

    public function dashboard_stats()
    {
        $stats = [];
        $stats["total_transactions"] = RequestTransaction::where('status', 'approved')->sum("amount");
        $stats["total_properties"] = InvestorsProperty::count();
        $stats["total_users"] = Investor::where('user_type', '!=', 'admin')->count();
        $stats["approved_request"] = ApprovedRequest::count();
        $stats["completed_requests"] = ApprovedRequest::where('status', 'completed')->count();

        return $this->successResponse("Stats", $stats);
    }

    public function all_users(Request $request)
    {
        $users = Investor::query();
        if ($request->has('filter')) {
            if (!is_null($request->input('filter.search'))) {
                $searchData = $request->input('filter.search');
                $users->where([['lname', 'LIKE', "%{$searchData}%"], ['user_type', '!=', 'admin']])
                    ->orWhere([['fname', 'LIKE', "%{$searchData}%"], ['user_type', '!=', 'admin']])
                    ->orWhere([['mname', 'LIKE', "%{$searchData}%"], ['user_type', '!=', 'admin']])
                    ->orWhere([['email', 'LIKE', "%{$searchData}%"], ['user_type', '!=', 'admin']])
                    ->orWhere([['phone', 'LIKE', "%{$searchData}%"], ['user_type', '!=', 'admin']]);
            }
            if (!is_null($request->input('filter.usertype'))) {
                $searchData = $request->input('filter.usertype');
                $users->where(['user_type' => $searchData]);
            }
        }
        return $this->successResponse("All users", $users->orderBy('created_at', 'desc')->paginate(50));
    }

    public function analytics(Request $request)
    {
        $stats = [];

        $stats['tots_admin'] = Investor::where('user_type', 'admin')->count();
        $stats['tots_user'] = Investor::where('user_type', 'user')->count();
        $stats['tots_marketer'] = Investor::where('user_type', 'marketer')->count();

        return $this->successResponse("Stats", $stats);
    }

    public function update_user_details(Request $request)
    {
        $request->validate([
            'lname' => 'required',
            'fname' => 'required',
            'gender' => 'required',
            'email' => 'required',
            'country' => 'required',
            'phone' => 'required',
        ]);
        $user = Investor::where('id', $request->id)->update([
            'fname' => $request->fname,
            'lname' => $request->lname,
            'mname' => $request->mname,
            'email' => $request->email,
            'phone' => $request->phone,
            'country' => $request->country,
            'gender' => $request->gender
        ]);
        return $this->successResponse("Action successful");
    }

    public function reset_user_password($id)
    {
        Investor::where('id', $id)->update([
            'password' => Hash::make('12345678')
        ]);
        return $this->successResponse("Action successful");
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            // 'amount' => 'required|integer',
            'location' => 'required|string',
            'description' => 'required|string',
            'status' => 'required|string',

        ]);

        if ($validator->fails()) {
            return $this->failureResponse("Invalid data passed", $validator->errors()->first());
        }
        $property = InvestorsProperty::where('id', $id)->first();
        if (!$property) return $this->failureResponse("Property not found");

        $property->name = request()->name;
        $property->amount = request()->amount;
        $property->location = request()->location;
        $property->description = request()->description;
        $property->square_meters_info = request()->square_meters_info;

        $property->status = request()->status;
        $property->property_link = $request->property_link;
        $property->video_link = $request->video_link;
        $property->type = $request->type;

        $property->save();
        return $this->successResponse("Action successful");
    }
    public function property_link(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'property_link' => 'required|string',
            'video_link' => 'required|sometimes|string',
        ]);

        if ($validator->fails()) {
            return $this->failureResponse("Invalid data passed", $validator->errors()->first());
        }
        $property = InvestorsProperty::where('id', $id)->first();
        if (!$property) return $this->failureResponse("Property not found");

        $property->property_link = request()->property_link;
        if ($request->has("video_link"))  $property->video_link = request()->video_link;

        $property->save();
        return $this->successResponse("Action successful");
    }

    public function change_image(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'image' => 'required',
        ]);


        $mainProperty = InvestorsProperty::find($request->id);
        if (!blank($mainProperty)) {
            $count = 0;
            $validExts = ["jpg", "jpeg", "png", "mp4", "svg"];
            foreach (request()->file('image') as  $value) {

                $ext[] = $value->extension();
                if (!in_array($value->extension(), $validExts)) {
                    $count++;
                }
            };
            if ($count > 0) return $this->failureResponse(__('property.mainproperty'), 'Some of the file extensions are not valid. Please upload a valid Image Extension');


            $images = $mainProperty->image ?? [];
            $filenames = $mainProperty->filename ?? [];
            foreach (request()->file('image') as  $value) {
                $image = time() . '_' . $value->getClientOriginalName();
                $path = $value->storeAs('public/images', $image);

                $path = url('/') . '/storage/images/' . $image;

                array_push($images, ['image' => $path]);
                array_push($filenames, ['filename' => $image]);
            };

            $mainProperty->image = $images;
            $mainProperty->filename = $filenames;
            $mainProperty->save();
        }

        return $mainProperty;
    }
    public function remove_single_image(Request $request)
    {
        $images = $request->image;
        $filename = $request->filename;
        $singleImage = $request->img["image"];
        $singleFileName = explode('/', $singleImage);
        $singleFileName = $singleFileName[count($singleFileName) - 1];
        if (count($images) > 1) {

            $mainProperty = InvestorsProperty::where('id', $request->id)->firstOrFail();

            if (file_exists(storage_path("/app/public/images/") . $singleFileName)) {
                unlink(storage_path("/app/public/images/") . $singleFileName);

                $fileNameind = array_search($singleFileName, array_column($filename, "filename"));
                $imageInd = array_search($singleImage, array_column($images, "image"));
                // return $ind;
                unset($images[$imageInd]);
                unset($filename[$fileNameind]);
                $images = array_values($images);
                $filename = array_values($filename);

                $mainProperty->filename = $filename;
                $mainProperty->image = $images;

                $mainProperty->save();
                return response()->json('Action successful', 200);
            }
            return response()->json('Could not find reference for this image', 408);


            return response()->json('Could not find file name for this image', 408);
        }
        return response()->json('You can only remove image if images is more than one', 408);
    }

    public function allocate(Request $request, $id)
    {
        $request->validate([
            'allocation_type' => 'required',
        ]);

        $approved_request = ApprovedRequest::where('id',  $id)->update([
            'allocated' => 'yes',
            'allocation_type' => $request->allocation_type
        ]);
        return $this->successResponse("Action successful");
    }
    public function survey_plan(Request $request, $id)
    {
        $request->validate([
            'survey_plan' => 'required',
        ]);

        $approved_request = ApprovedRequest::where('id',  $id)->update([
            'survey_plan' => $request->survey_plan
        ]);
        return $this->successResponse("Action successful");
    }
    public function assignment_type(Request $request, $id)
    {
        $request->validate([
            'deed_of_assignment_type' => 'required',
        ]);

        $approved_request = ApprovedRequest::where('id',  $id)->update([
            'deed_of_assignment_type' => $request->deed_of_assignment_type,
            'deed_of_assignment' => 'assigned'
        ]);
        return $this->successResponse("Action successful");
    }

    public function toggle_status($id)
    {
        $mainProperty = InvestorsProperty::where('id', $id)->update([
            'status' => request()->status
        ]);

        return $this->successResponse(__('mainproperty.updated'), $mainProperty);
    }

    public function assign_key(Request $request)
    {
        $approved_request = ApprovedRequest::where('id', $request->id)->update([
            'key_allocated' => 'yes'
        ]);
        return $this->successResponse('Action successful');
    }
    public function assign_deed_of_assignment(Request $request)
    {
        $approved_request = ApprovedRequest::where('id', $request->id)->update([
            'deed_of_assignment' => 'assigned'
        ]);
        return $this->successResponse('Action successful');
    }

    public function destroy($id)
    {
        $delete_main_property = InvestorsProperty::where('id', $id)->first();
        if (!$delete_main_property) return $this->failureResponse("Not found");
        $filenames_decoded = $delete_main_property->filename ?? [];
        try {
            foreach ($filenames_decoded as $value) {
                // return $value->filename;
                # code...
                if (storage_path("/app/public/images/" . $value['filename'])) {

                    # code...
                    unlink(storage_path("/app/public/images/" . $value['filename']));
                }
            }

            $delete_main_property->delete();
            return $this->successResponse(__('mainproperty.delete'));

            return $this->failureResponse(__('mainproperty.error'), null);
        } catch (\Throwable $th) {

            return $this->failureResponse(__('mainproperty.error'), null, 500);
        }
    }

    public function attachMarkerters(Request $request, $property_id)
    {
        foreach ($request->all() as $marketer) {
            $result = DB::table('sell_properties')
                ->join('approved_requests', 'sell_properties.approved_request_id', '=', 'approved_requests.id')
                ->join('requests', 'approved_requests.request_id', '=', 'requests.id')
                ->select('sell_properties.*', 'approved_requests.*', 'requests.*')
                ->where('sell_properties.marketer_id', $marketer["id"])
                ->first();
            if ($marketer['amount'] != '') {
                SellProperty::updateOrCreate([
                    'approved_request_id' =>  $property_id,
                    'marketer_id' => $marketer['id'],
                    'user_id' => Auth::user()->id,
                ], [
                    'approved_request_id' =>  $property_id,
                    'marketer_id' => $marketer['id'],
                    'amount' => $marketer['amount'],
                    'user_id' => Auth::user()->id,
                ]);

                $user = Investor::where('id', $marketer['id'])->first();
                if (empty($user)) {
                    return $this->failureResponse("Marketer does not exist");
                }
                $payload = [
                    'request' => $result,
                    'amount' => $marketer['amount'],
                    'name' => $user->fname
                ];
                try {
                    $check = SellProperty::where(['approved_request_id' =>  $property_id, 'marketer_id' => $marketer['id']]);
                    if ($check->first()->is_notified == 'false') {
                        Mail::to($user->email)->send(new NotifyMarketer($payload));
                        $check->update([
                            'is_notified' => 'true'
                        ]);
                    }
                } catch (\Exception $e) {
                    return $e->getMessage();
                }
            }
        }
        return $this->successResponse("Action successfull");
    }

    public function deleteMarkerters(Request $request, $approve_request_id)
    {
        $property = SellProperty::where(['approved_request_id' => $approve_request_id, 'marketer_id' => $request->marketer_id, 'user_id' => Auth::user()->id]);
        if ($property->first()->status == 'pending') {
            $property->delete();
            return $this->successResponse("Action successful");
        } else {
            return $this->failureResponse("Can't delete property");
        }
        // return $property->first();
    }

    public function getMarketersProperty(Request $request)
    {
        // return Auth::user()->id;
        $property = SellProperty::join('approved_requests', 'approved_requests.id', 'sell_properties.approved_request_id')
            ->join('investors_properties', 'investors_properties.id', 'approved_requests.investor_property_id')
            ->select('*', 'investors_properties.amount as property_price', 'sell_properties.amount as amount_to_be_sold', 'sell_properties.status as property_status')
            ->where(['marketer_id' => Auth::user()->id]);

        if ($request->has('filter')) {
            if (!is_null($request->input('filter.status'))) {
                $searchData = $request->input('filter.status');
                $property->where('sell_properties.status', $searchData);
            }
        }
        return $this->successResponse("Action successful", $property->paginate(50));
    }

    public function manageMarketers(Request $request)
    {
        // return 1234;
        $property = SellProperty::with('marketer', 'property', 'approved_request.property')->where(['user_id' => Auth::user()->id]);
        if ($request->has('filter')) {
            if (!is_null($request->input('filter.status'))) {
                $searchData = $request->input('filter.status');
                $property->where('status', $searchData);
            }
        }
        return $this->successResponse("Action successful", $property->paginate(50));
    }

    public function ChangeStatus(Request $request, $approved_request_id, $marketer_id)
    {
        $check = SellProperty::where(['approved_request_id' => $approved_request_id])->get();
        $soldPropertyExists = false;

        foreach ($check as $key => $value) {
            if ($value->status == 'sold' && $request->status == 'sold') {
                $soldPropertyExists = true;
                $this->failureResponse("Property has already been sold");
                break;
            }
        }

        if (!$soldPropertyExists) {
            $property = SellProperty::where(['approved_request_id' => $approved_request_id, 'marketer_id' => $marketer_id])
                ->update([
                    'status' => $request->status
                ]);
        }



        return $this->successResponse("Action successful", $property);
    }
    
    public function deleteUserDetails($id)
    {
        $request = ModelRequest::where('investor_id', $id)->pluck('id')->toArray();
        if(!empty($request)){
            RequestTransaction::where('investor_id', $id)->delete();
            RequestConversation::whereIn('request_id', $request)->delete();
            ApprovedRequest::whereIn('request_id', $request)->delete();
            $request = ModelRequest::where('investor_id', $id)->delete();
        }
        $user = User::where('id', $id)->delete();

        return $this->successResponse("Action successful", $user);
    }
}
