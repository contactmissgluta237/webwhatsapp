<?php

return [
    /**
     * Options: tailwind | bootstrap-4 | bootstrap-5.
     */
    'theme' => 'bootstrap-5',

    /**
     * Enable Blade Directives (Not required if automatically injecting or using bundler approaches)
     */
    'enable_blade_directives' => false,

    /**
     * Use JSON Translations instead of PHP Array
     */
    'use_json_translations' => true,

    /**
     * Configuration options for Events
     */
    'events' => [
        /**
         * Enable or disable passing the user from Laravel's Auth service to events
         */
        'enableUserForEvent' => true,
    ],

];
