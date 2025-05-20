<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactNotificationRecipient;
use App\Notifications\ContactSubmissionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    /**
     * Handle the contact form submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:100',
            'message' => 'required|string',
            'newsletter' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the contact record
        $contact = Contact::create([
            'full_name' => $request->fullName,
            'email' => $request->email,
            'phone' => $request->phone,
            'subject' => $request->subject,
            'message' => $request->message,
            'newsletter' => $request->newsletter ?? false,
        ]);

        // Send notification email to admin about new contact
        try {
            // Get the notification email from config
            $notificationEmail = config('mail.contact_form_recipient', 'info.mubarakproducts@gmail.com');

            // Log the recipient email for debugging
            Log::info('Sending contact notification to: ' . $notificationEmail);

            $recipient = new ContactNotificationRecipient($notificationEmail);
            $recipient->notify(new ContactSubmissionNotification($contact));

            // If you want to send to multiple recipients, you can uncomment and modify this code:
            /*
            $additionalEmails = ['sales@mubarakproducts.com', 'admin@mubarakproducts.com'];
            foreach ($additionalEmails as $email) {
                $recipient = new ContactNotificationRecipient($email);
                $recipient->notify(new ContactSubmissionNotification($contact));
            }
            */

        } catch (\Exception $e) {
            // Log the error but don't return it to the user
            // The form submission was still successful even if the email fails
            Log::error('Failed to send contact form notification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully!',
            'data' => $contact
        ], 201);
    }
}
