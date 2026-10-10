<?php

return [

    /*
    | The live demo on the landing page. Its dates are moved at every page load
    | so the muhurtham is always about `demo_days_ahead` days away and the
    | countdown never runs out (App\Support\InvitationPage).
    */

    'demo_slug' => env('DEMO_INVITATION_SLUG', 'priya-karthik-demo'),

    'demo_days_ahead' => 45,

    // Event dates and times are typed in Indian time. Version 1 sells in India only.
    'timezone' => 'Asia/Kolkata',

    // The "made with" link at the end of every invitation. The name is an open
    // decision in PLAN.md; the link goes to the customer site.
    'brand' => [
        'name' => env('BRAND_NAME', 'Invitations'),
        'url' => env('FRONTEND_CLIENT_URL', 'http://localhost:5173'),
    ],

];
