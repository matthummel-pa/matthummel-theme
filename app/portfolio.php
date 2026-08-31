<?php

/**
 * Portfolio entry — decompresses shipped payload on first load.
 */
$_mhBuilt = __DIR__ . '/.portfolio.built.php';
if (! is_readable($_mhBuilt)) {
    $_mhB64 = '';
    for ($_mhI = 0; $_mhI < 4; $_mhI++) {
        $_mhPart = __DIR__ . '/portfolio.zlib.b64.' . $_mhI;
        if (! is_readable($_mhPart)) {
            throw new \RuntimeException('Missing ' . $_mhPart);
        }
        $_mhB64 .= (string) file_get_contents($_mhPart);
    }
    $_mhCode = zlib_decode(base64_decode($_mhB64));
    if (! is_string($_mhCode) || ! str_starts_with($_mhCode, '<?php')) {
        throw new \RuntimeException('Invalid portfolio payload');
    }
    if (false === file_put_contents($_mhBuilt, $_mhCode)) {
        throw new \RuntimeException('Could not materialize portfolio');
    }
    unset($_mhB64, $_mhCode, $_mhI, $_mhPart);
}
require $_mhBuilt;
unset($_mhBuilt);
