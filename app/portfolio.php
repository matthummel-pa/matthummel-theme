<?php

/**
 * Portfolio entry — decompresses shipped payload on first load.
 */
$_mhBuilt = __DIR__.'/.portfolio.built.php';
if (! is_readable($_mhBuilt)) {
    $_mhB64 = '';
    for ($_mhI = 0; $_mhI < 4; $_mhI++) {
        $_mhPart = __DIR__.'/portfolio.zlib.b64.'.$_mhI;
        if (! is_readable($_mhPart)) {
            throw new RuntimeException('Missing '.$_mhPart);
        }
        $_mhB64 .= (string) file_get_contents($_mhPart);
    }
    // Repair known single-byte corruptions from upload transport.
    $_mhFixes = [
        782 => 'N',
        3922 => '9',
        17614 => 'f',
        22818 => 'l',
        22819 => 't',
    ];
    foreach ($_mhFixes as $_mhPos => $_mhCh) {
        if (! isset($_mhB64[$_mhPos])) {
            throw new RuntimeException('Portfolio payload shorter than expected');
        }
        $_mhB64[$_mhPos] = $_mhCh;
    }
    $_mhCode = zlib_decode(base64_decode($_mhB64));
    if (! is_string($_mhCode) || ! str_starts_with($_mhCode, '<?php')) {
        throw new RuntimeException('Invalid portfolio payload');
    }
    if (file_put_contents($_mhBuilt, $_mhCode) === false) {
        throw new RuntimeException('Could not materialize portfolio');
    }
    unset($_mhB64, $_mhCode, $_mhI, $_mhPart, $_mhFixes, $_mhPos, $_mhCh);
}
require $_mhBuilt;
unset($_mhBuilt);
