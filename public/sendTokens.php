<?php
require 'vendor/autoload.php';

if (($argc !== 3 && $argc !== 4) || ($argc === 4 && strlen($argv[3]) !== 18)) {
    echo "Usage: php sendTokens.php <destinationAddress> <amountInWholeNumber> <optional:amountWith18Zeros>" . PHP_EOL;
    echo "Example to send 2 tokens: php sendTokens.php 0xB3F0c9d503104163537Dd741D502117BBf6aF8f1 16" . PHP_EOL;
    echo "Example to send 0.2 tokens: php sendTokens.php 0xB3F0c9d503104163537Dd741D502117BBf6aF8f1 0 200000000000000000" . PHP_EOL;
    echo "Example to send 2.5 tokens: php sendTokens.php 0xB3F0c9d503104163537Dd741D502117BBf6aF8f1 2 500000000000000000" . PHP_EOL;
    exit(1);
}
$destinationAddress = $argv[1];

$amountInWholeNumber = null;
if ($argc === 3) {
    $amountInWholeNumber = intval($argv[2]) * (10 ** 18);
} else {
    $amountInWholeNumber = intval($argv[2] . $argv[3]);
}

use Web3\Web3;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Web3\Contract;
use Web3\Utils;
use Web3p\EthereumTx\Transaction;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '../.env');
$dotenv->load();

$infuraProjectId = $_ENV['INFURA_PROJECT_ID'];
$infuraProjectSecret = $_ENV['INFURA_PROJECT_SECRET'];
$contractAddress = $_ENV['TOKEN_CONTRACT_ADDRESS'];
$fromAccount = $_ENV['SOURCE_ACCOUNT_ADDRESS'];
$fromAccountPrivateKey = $_ENV['SOURCE_ACCOUNT_PRIVATE_KEY'];
$secondsToWaitForReceiptString = $_ENV['SECONDS_TO_WAIT_FOR_RECEIPT'];
$secondsToWaitForReceipt = intval($secondsToWaitForReceiptString);
$factorToMultiplyGasEstimateString = $_ENV['FACTOR_TO_MULTIPLY_GAS_ESTIMATE'];
$factorToMultiplyGasEstimate = intval($factorToMultiplyGasEstimateString);

$chainIds = [
    'Mainnet' => 1,
    'Ropsten' => 3
];

$infuraHosts = [
    'Mainnet' => 'mainnet.infura.io',
    'Ropsten' => 'ropsten.infura.io'
];

$chainId = $chainIds[$_ENV['CHAIN_NAME']];
$infuraHost = $infuraHosts[$_ENV['CHAIN_NAME']];

$abi = file_get_contents(__DIR__ . '/../resources/Erc777TokenAbiArray.json');

$contract = new Contract("https://:$infuraProjectSecret@$infuraHost/v3/$infuraProjectId", $abi);

$eth = $contract->eth;

$contract->at($contractAddress)->call('balanceOf', $fromAccount, [
    'from' => $fromAccount
], function ($err, $results) use ($contract) {
    if ($err !== null) {
        echo $err->getMessage() . PHP_EOL;
    }
    if (isset($results)) {
        foreach ($results as &$result) {
            $bn = Utils::toBn($result);
            echo 'BEFORE fromAccount balance ' . $bn->toString() . PHP_EOL;
        }
    }
});

$contract->at($contractAddress)->call('balanceOf', $destinationAddress, [
    'from' => $destinationAddress
], function ($err, $results) use ($contract) {
    if ($err !== null) {
        echo $err->getMessage() . PHP_EOL;
    }
    if (isset($results)) {
        foreach ($results as &$result) {
            $bn = Utils::toBn($result);
            echo 'BEFORE destinationAddress balance ' . $bn->toString() . PHP_EOL;
        }
    }
});

$rawTransactionData = '0x' . $contract->at($contractAddress)->getData('transfer', $destinationAddress, $amountInWholeNumber);

$transactionCount = null;
$eth->getTransactionCount($fromAccount, function ($err, $transactionCountResult) use(&$transactionCount) {
    if($err) { 
        echo 'getTransactionCount error: ' . $err->getMessage() . PHP_EOL; 
    } else {
        $transactionCount = $transactionCountResult;
    }
});
echo "\$transactionCount=$transactionCount" . PHP_EOL;

$transactionParams = [
    'nonce' => "0x" . dechex($transactionCount->toString()),
    'from' => $fromAccount,
    'to' =>  $contractAddress,
    'gas' =>  '0x' . dechex(8000000),
    'value' => '0x0',
    'data' => $rawTransactionData
];

$estimatedGas = null;
$eth->estimateGas($transactionParams, function ($err, $gas) use (&$estimatedGas) {
    if ($err) {
        echo 'estimateGas error: ' . $err->getMessage() . PHP_EOL; 
    } else {
        $estimatedGas = $gas;
    }
});
echo "\$estimatedGas=$estimatedGas" . PHP_EOL;

$gasPriceMultiplied = hexdec(dechex($estimatedGas->toString())) * $factorToMultiplyGasEstimate;
echo "\$gasPriceMultiplied=$gasPriceMultiplied" . PHP_EOL;

$transactionParams['gasPrice'] = '0x' . dechex($gasPriceMultiplied);
$transactionParams['chainId'] = $chainId;
$tx = new Transaction($transactionParams);
$signedTx = '0x' . $tx->sign($fromAccountPrivateKey);
$txHash = null;
$eth->sendRawTransaction($signedTx, function ($err, $txResult) use (&$txHash) {
    if($err) { 
        echo 'transaction error: ' . $err->getMessage() . PHP_EOL; 
    } else {
        $txHash = $txResult;
    }
});
echo "\$txHash=$txHash" . PHP_EOL;

$txReceipt = null;
echo "Waiting for transaction receipt";
for ($i=0; $i <= $secondsToWaitForReceipt; $i++) {
    echo '.';
    $eth->getTransactionReceipt($txHash, function ($err, $txReceiptResult) use(&$txReceipt) {
        if($err) { 
            echo 'getTransactionReceipt error: ' . $err->getMessage() . PHP_EOL; 
        } else {
            $txReceipt = $txReceiptResult;
        }
    });

    if ($txReceipt) {
        echo PHP_EOL;
        break;
    }

    sleep(1);
}
$txStatus = $txReceipt->status;
echo "\$txStatus=$txStatus" . PHP_EOL;

$contract->at($contractAddress)->call('balanceOf', $fromAccount, [
    'from' => $fromAccount
], function ($err, $results) use ($contract) {
    if ($err !== null) {
        echo $err->getMessage() . PHP_EOL;
    }
    if (isset($results)) {
        foreach ($results as &$result) {
            $bn = Utils::toBn($result);
            echo 'AFTER fromAccount balance ' . $bn->toString() . PHP_EOL;
        }
    }
});

$contract->at($contractAddress)->call('balanceOf', $destinationAddress, [
    'from' => $destinationAddress
], function ($err, $results) use ($contract) {
    if ($err !== null) {
        echo $err->getMessage() . PHP_EOL;
    }
    if (isset($results)) {
        foreach ($results as &$result) {
            $bn = Utils::toBn($result);
            echo 'AFTER destinationAddress balance ' . $bn->toString() . PHP_EOL;
        }
    }
});
