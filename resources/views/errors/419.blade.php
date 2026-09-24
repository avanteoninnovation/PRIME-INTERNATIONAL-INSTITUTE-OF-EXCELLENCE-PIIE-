@include('errors._piie', ['code' => 419, 'heading' => 'Your session has expired',
    'message' => 'For your security, the page expired before the form was submitted. Please sign in again and repeat the last step.',
    'actions' => ['Sign in again' => url('/login'), 'Go back' => 'back']])
