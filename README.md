## Waiting vs Not Waiting
The `$transactionCount` obtained by calling `getTransactionCount()` is only accurate if ALL the previously submitted transactions have been mined.
There's some code that waits for the transaction to be mined.
If the wait is removed or bypassed then it will be necessary to manually count how many transactions were submitted and manually increment `$transactionCount`.

Search for information on the **ethereum nonce** to better understand the importance of the transaction count.

## Requirements
1. Manually install PHP 7.x (one of the packages doesn't support 8.x yet).
2. Uncomment `extension=gmp` in your php.ini file.
3. Manually php composer from https://getcomposer.org/.

## Inital Setup

If this is the first time you've cloned this repository then run

1. Run command `composer install`
2. Create `.env` file from `.env.example` file

## Running
`php public\sendTokens.php 0xB3F0c9d503104163537Dd741D502117BBf6aF8f1 2 500000000000000000`

## Debugging
1. Follow instructions at https://xdebug.org/wizard
   - On Windows, if not specified make sure to rename the DLL to exactly `php_xdebug.dll`.
2. Add zend_extension = xdebug
3. Start your debugger
4. Run command `php -dxdebug.mode=debug -dxdebug.start_with_request=yes public\sendTokens.php 0xB3F0c9d503104163537Dd741D502117BBf6aF8f1 2 500000000000000000`
