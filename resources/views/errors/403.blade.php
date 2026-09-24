@include('errors._piie', ['code' => 403, 'heading' => 'Access denied',
    'message' => 'You do not have permission to open this page. If you believe you should, please ask your school administrator to review your access.',
    'actions' => ['Go to my dashboard' => url('/'), 'Go back' => 'back']])
