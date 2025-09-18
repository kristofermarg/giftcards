<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$cards = App\Models\Giftcard::whereNotNull('passkit_member_id')->get(['code','passkit_member_id']);
foreach ($cards as $card) {
    echo $card->code . " => " . $card->passkit_member_id . "\n";
}
