<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\OrganizationDocuments;
use App\Models\QuestionAnswer;
use Illuminate\Http\Request;

class CommonController extends Controller
{
    public function getQuestionAnswer(){

        $question_answer = QuestionAnswer::all();

        return response()->json([
            'success' => true,
            'data' => $question_answer
        ]);
    }

    public function getCities(){

        $cities = City::all();

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    public function getOrganizationDocuments(){

        $docs = OrganizationDocuments::all();

        return response()->json([
            'success' => true,
            'data' => $docs
        ]);
    }
}
