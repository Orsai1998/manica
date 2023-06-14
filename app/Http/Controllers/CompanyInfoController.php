<?php

namespace App\Http\Controllers;

use App\Models\CompanyInfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CompanyInfoController extends Controller
{

    public function create(Request $request){

        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required | exists:bookings,id',
            'BIN' => 'required',
            'name' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }
        $request->merge(['user_id' => $user->id]);
        CompanyInfo::create($request->all());

        return response()->json([
            'success' => true
        ]);
    }

    public function index(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'booking_id' => 'required | exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $company =  CompanyInfo::where('booking_id', $request->booking_id)->where('user_id', $user->id)->first();

        return response()->json([
            'success' => true,
             'company_info' => $company
        ]);

    }

    public function delete(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'company_info_id' => 'required | exists:company_infos,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'=>false,
                'message'=>$validator->errors()
            ]);
        }

        $company =  CompanyInfo::find($request->company_info_id);

        $company->delete();
        return response()->json([
            'success' => true,
        ]);
    }

}
