<?php

/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Juzaweb\Modules\Core\Facades\Breadcrumb;
use Juzaweb\Modules\Core\Http\Controllers\AdminController;
use Juzaweb\Modules\Payment\Facades\PaymentManager;
use Juzaweb\Modules\Payment\Http\DataTables\MethodsDataTable;
use Juzaweb\Modules\Payment\Http\Requests\PaymentMethodRequest;
use Juzaweb\Modules\Payment\Models\PaymentMethod;

class MethodController extends AdminController
{
    public function index(MethodsDataTable $dataTable)
    {
        Breadcrumb::add(__('Payment Methods'));

        $createUrl = action([static::class, 'create']);
        $paymentMethods = PaymentMethod::withTranslation()
            ->where('active', true)
            ->get();

        return $dataTable->render(
            'payment::method.index',
            [
                'title' => __('payment::translation.payment_methods'),
                'description' => __('payment::translation.payment_methods_description'),
                'paymentMethods' => $paymentMethods,
                'createUrl' => $createUrl,
            ]
        );
    }

    public function create()
    {
        Breadcrumb::add(__('Payment Methods'), admin_url('payment-methods'));

        Breadcrumb::add(__('Create Payment Method'));

        $locale = $this->getFormLanguage();
        $backUrl = action([static::class, 'index']);

        return view('payment::method.form', [
            'model' => new PaymentMethod(),
            'action' => action([static::class, 'store']),
            'locale' => $locale,
            'backUrl' => $backUrl,
        ]);
    }

    public function edit(Request $request, string $id)
    {
        $method = PaymentMethod::findOrFail($id);

        Breadcrumb::add(__('Payment Methods'), admin_url('payment-methods'));

        Breadcrumb::add(__('Edit Payment Method :name', ['name' => $method->name]));

        $locale = $this->getFormLanguage();
        $method->setDefaultLocale($locale);
        $backUrl = action([static::class, 'index']);

        return view('payment::method.form', [
            'model' => $method,
            'action' => action([static::class, 'update'], [$id]),
            'locale' => $locale,
            'backUrl' => $backUrl,
        ]);
    }

    public function store(PaymentMethodRequest $request)
    {
        $locale = $this->getFormLanguage();

        DB::transaction(
            function () use ($request, $locale) {
                $method = new PaymentMethod($request->validated());
                $method->setDefaultLocale($locale);
                $method->save();
            }
        );

        return $this->success(__('Payment method saved successfully.'));
    }

    public function update(PaymentMethodRequest $request, string $id)
    {
        $locale = $this->getFormLanguage();
        DB::transaction(
            function () use ($request, $locale, $id) {
                $input = $request->validated();
                $method = PaymentMethod::findOrFail($id);

                $input['config'] = array_merge(
                    $method->config ?? [],
                    $input['config']
                );

                $method->setDefaultLocale($locale);
                $method->update($input);
            }
        );

        return $this->success(
            __('Payment method updated successfully.')
        );
    }

    public function getData(string $driver): JsonResponse
    {
        return response()->json([
            'config' => PaymentManager::renderConfig($driver),
        ]);
    }
}
