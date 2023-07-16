<?php

namespace App\Http\Controllers;

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
}
