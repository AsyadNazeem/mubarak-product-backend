@component('mail::message')
    # New Contact Form Submission

    You have received a new message from your website contact form.

    ## Details

    **Name:** {{ $contact->full_name }}
    **Email:** {{ $contact->email }}
    @if($contact->phone)
        **Phone:** {{ $contact->phone }}
    @endif
    **Subject:** {{ $contact->subject }}

    ## Message
    {{ $contact->message }}

    @if($contact->newsletter)
        _User has subscribed to the newsletter._
    @endif

    This message was submitted on {{ $contact->created_at->format('F j, Y \a\t g:i a') }}

    @component('mail::button', ['url' => url('/admin/contacts'), 'color' => 'primary'])
        View All Submissions
    @endcomponent

    Thank you,
    {{ config('app.name') }}
@endcomponent
