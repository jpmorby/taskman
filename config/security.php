<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vulnerability disclosure contact
    |--------------------------------------------------------------------------
    |
    | Advertised at /.well-known/security.txt (RFC 9116). Set SECURITY_CONTACT
    | in .env to the mailbox that actually reads disclosures; the default is a
    | placeholder so that a real address is never committed to the repository.
    |
    */

    'contact' => env('SECURITY_CONTACT', 'security@example.com'),

];
