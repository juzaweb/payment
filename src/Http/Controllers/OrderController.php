<?php

namespace Juzaweb\Modules\Payment\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Juzaweb\Modules\Core\Facades\Breadcrumb;
use Juzaweb\Modules\Core\Http\Controllers\AdminController;
use Juzaweb\Modules\Payment\Http\DataTables\OrdersDataTable;
use Juzaweb\Modules\Payment\Http\Requests\OrderActionsRequest;
use Juzaweb\Modules\Payment\Http\Requests\OrderRequest;
use Juzaweb\Modules\Payment\Models\Order;

class OrderController extends AdminController
{
    public function index(OrdersDataTable $dataTable)
    {
        Breadcrumb::add(__('Orders'));

        return $dataTable->render(
            'payment::order.index'
        );
    }

    public function edit(string $id)
    {
        Breadcrumb::add(__('Orders'), admin_url('orders'));

        Breadcrumb::add(__('Create Orders'));

        $model = Order::findOrFail($id);
        $backUrl = action([static::class, 'index']);

        return view(
            'payment::order.form',
            [
                'action' => action([static::class, 'update'], [$id]),
                'model' => $model,
                'backUrl' => $backUrl,
            ]
        );
    }

    public function update(OrderRequest $request, string $id)
    {
        $model = Order::findOrFail($id);

        $model = DB::transaction(
            function () use ($request, $model) {
                $data = $request->validated();

                $model->update($data);

                return $model;
            }
        );

        return $this->success([
            'redirect' => action([static::class, 'index']),
            'message' => __('Order :name updated successfully', ['name' => $model->name]),
        ]);
    }

    public function bulk(OrderActionsRequest $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        $models = Order::whereIn('id', $ids)->get();

        foreach ($models as $model) {
        }

        return $this->success([
            'message' => __('Bulk action performed successfully'),
        ]);
    }
}
