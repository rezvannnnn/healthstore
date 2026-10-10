<?php

namespace App\Http\Controllers;

use App\Models\StoreSetting;
use Inertia\Inertia;
use Inertia\Response;

class StoreInformationController extends Controller
{
    public function __invoke(string $section): Response
    {
        $titles = ['contact' => 'تماس و پشتیبانی', 'shipping' => 'شرایط ارسال', 'returns' => 'شرایط مرجوعی', 'privacy' => 'حریم خصوصی'];
        abort_unless(isset($titles[$section]), 404);

        return Inertia::render('StoreInformation', [
            'section' => $section, 'title' => $titles[$section], 'content' => StoreSetting::getValue($section.'_policy', ''),
            'supportPhone' => StoreSetting::getValue('support_phone', ''), 'supportEmail' => StoreSetting::getValue('support_email', ''),
            'storeAddress' => StoreSetting::getValue('store_address', ''),
        ]);
    }
}
