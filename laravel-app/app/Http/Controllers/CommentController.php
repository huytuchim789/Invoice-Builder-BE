<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Pin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CommentController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCommentRequest $request)
    {
        try {
            $validatedData = $request->validated();

            $pin = Pin::where('coordinate_X', $request->pin['xRatio'])
                ->where('coordinate_Y', $request->pin['yRatio'])
                ->where('invoice_id', $validatedData['invoice_id'])
                ->first();

            if (!$pin) {
                $pin = Pin::create([
                    'number' => $validatedData['number'],
                    'coordinate_X' => $request->pin['xRatio'],
                    'coordinate_Y' => $request->pin['yRatio'],
                    'invoice_id' => $validatedData['invoice_id'],
                    'user_id' => auth()->user()->id,
                ]);
            }
            $comment = Comment::create([
                'pin_id' => $pin->id,
                'user_id' => auth()->user()->id,
                'content' => $validatedData['message'],
            ]);
            if($user=User::where('email',$comment->pin->invoice->customer->email)->first()){
                if(auth()->user()->id==$comment->pin->invoice->user->id){
                    $user->sendCommentNotification($comment,auth()->user(),$comment->pin->invoice->id);
                }
                else{
                    $comment->pin->invoice->user->sendCommentNotification($comment,auth()->user(),$comment->pin->invoice->id);
                }
            }
            // Retrieve the pin list with comments
            $pins = Pin::with(['comments.user'])->where(["invoice_id" => $validatedData['invoice_id']])->get();

            return Response::customJson(200, $pins, "Success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function show(Comment $comment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function edit(Comment $comment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Comment $comment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Comment $comment
     * @return \Illuminate\Http\Response
     */
    public function destroy(Comment $comment)
    {
        //
    }
}
