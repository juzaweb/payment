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

use Juzaweb\Core\Facades\Breadcrumb;
use Juzaweb\Core\Http\Controllers\AdminController;
use Juzaweb\Modules\Payment\Http\DataTables\MethodsDataTable;
use Juzaweb\Modules\Payment\Models\PaymentMethod;

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

        return view('payment::method.form', [
            'model' => new PaymentMethod(),
            'action' => action([static::class, 'store']),
        ]);
    }

    public function edit(PaymentMethod $method)
    {
        Breadcrumb::add(__('Payment Methods'), admin_url('payment-methods'));

        Breadcrumb::add(__('Edit Payment Method'));

        return view('payment::method.form', [
            'model' => $method,
            'action' => admin_url("payment-methods/{$method->id}"),
        ]);
    }

    public function store()
    {
        $data = request()->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
        ]);

        $method = PaymentMethod::updateOrCreate(
            ['id' => request('id')],
            $data
        );

        return $this->success(__('Payment method saved successfully.'));
    }
}
