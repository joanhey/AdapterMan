<?php

// Only with libs than need to be PHP compliant
// PHP modifies field names containing these characters to maintain compatibility with the deprecated register_globals feature
dataset('ext vars to underscore', [
            ['a b', 'a_b'],
            ['a.b', 'a_b'],
            ['a[b', 'a_b'],
            // Also from chr(128) - chr(159) are converted to _
            
            // Not converted
            ['a_b', 'a_b'],
            ['a]b', 'a]b'],
            ['a-b', 'a-b'],
            ['a/b', 'a/b'],
            ['a\b', 'a\b'],
            ['a,b', 'a,b'],          
        ]
);