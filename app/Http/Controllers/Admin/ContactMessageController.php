<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * Display a listing of the customer contact messages
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all contact messages, ordered by most recent first
        $messages = Contact::orderBy('created_at', 'desc')->get();

        return response()->json($messages);
    }

    /**
     * Mark a message as read
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function markAsRead($id)
    {
        $message = Contact::findOrFail($id);
        $message->read = true;
        $message->save();

        return response()->json(['success' => true]);
    }

    /**
     * Delete a message
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $message = Contact::findOrFail($id);
        $message->delete();

        return response()->json(['success' => true]);
    }
}
