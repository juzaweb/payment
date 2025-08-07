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
use Juzaweb\Core\Facades\Breadcrumb;
use Juzaweb\Core\Http\Controllers\AdminController;
use Juzaweb\Modules\Payment\Http\DataTables\MethodsDataTable;
use Juzaweb\Modules\Payment\Http\Requests\PaymentMethodRequest;
use Juzaweb\Modules\Payment\Models\PaymentMethod;
use Juzaweb\Modules\Payment\Facades\PaymentManager;

class MethodController extends AdminController
{
    public function index(MethodsDataTable $dataTable)
    {
        Breadcrumb::add(__('Payment Methods'));

        return $dataTable->render(
            'payment::method.index',
            [
                'title' => __('payment::translation.payment_methods'),
                'description' => __('payment::translation.payment_methods_description'),
            ]
        );
    }

    public function create()
    {
        Breadcrumb::add(__('Payment Methods'), admin_url('payment-methods'));

        Breadcrumb::add(__('Create Payment Method'));

        $locale = $this->getFormLanguage();

        return view('payment::method.form', [
            'model' => new PaymentMethod(),
            'action' => action([static::class, 'store']),
            'locale' => $locale,
        ]);
    }

    public function edit(Request $request, int $id)
    {
        $method = PaymentMethod::findOrFail($id);

        Breadcrumb::add(__('Payment Methods'), admin_url('payment-methods'));

        Breadcrumb::add(__('Edit Payment Method :name', ['name' => $method->name]));

        $locale = $this->getFormLanguage();
        $method->setDefaultLocale($locale);

        return view('payment::method.form', [
            'model' => $method,
            'action' => admin_url("payment-methods/{$method->id}"),
            'locale' => $locale,
        ]);
    }

    public function store(PaymentMethodRequest $request)
    {
        $method = PaymentMethod::create($request->safe()->all());

        return $this->success(__('Payment method saved successfully.'));
    }

    public function update(PaymentMethodRequest $request, int $id)
    {
        $method = PaymentMethod::findOrFail($id);

        $method->update($request->safe()->all());

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
