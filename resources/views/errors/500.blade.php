@include('errors._piie', ['code' => 500, 'heading' => 'Something went wrong',
    'message' => 'An unexpected problem stopped this page from loading. It has been recorded for the technical team. Please try again, or return to your dashboard.',
    'actions' => ['Try again' => 'reload', 'Go to my dashboard' => url('/'), 'Go back' => 'back']])
