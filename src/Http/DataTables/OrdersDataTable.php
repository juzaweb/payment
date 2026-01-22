<?php

namespace Juzaweb\Modules\Payment\Http\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Juzaweb\Modules\Admin\Traits\OrderableDataTable;
use Juzaweb\Modules\Core\DataTables\Action;
use Juzaweb\Modules\Core\DataTables\Column;
use Juzaweb\Modules\Core\DataTables\DataTable;
use Juzaweb\Modules\Payment\Enums\OrderDeliveryStatus;
use Juzaweb\Modules\Payment\Enums\OrderPaymentStatus;
use Juzaweb\Modules\Payment\Models\Order;
use Yajra\DataTables\EloquentDataTable;

class OrdersDataTable extends DataTable
{
    protected string $actionUrl = 'orders/bulk';

    protected array $rawColumns = ['actions', 'checkbox', 'payment_status', 'delivery_status'];

    public function query(Order $model): Builder
    {
        return $model->newQuery();
    }

    public function renderColumns(EloquentDataTable $builder): EloquentDataTable
    {
        $builder->editColumn(
            'payment_status',
            function (Order $model) {
                $status = $model->payment_status;
                $color = match ($status) {
                    OrderPaymentStatus::COMPLETED => 'success',
                    default => 'warning',
                };

                $label = $status?->label() ?? $model->payment_status;
                return '<span class="badge badge-' . $color . '">' . $label . '</span>';
            }
        );

        $builder->editColumn(
            'delivery_status',
            function (Order $model) {
                $status = $model->delivery_status;
                $color = match ($status) {
                    OrderDeliveryStatus::COMPLETED => 'success',
                    OrderDeliveryStatus::PROCESSING => 'info',
                    OrderDeliveryStatus::SHIPPING => 'primary',
                    OrderDeliveryStatus::CANCELED => 'danger',
                    default => 'warning',
                };

                $label = $status?->label() ?? $model->delivery_status;
                return '<span class="badge badge-' . $color . '">' . $label . '</span>';
            }
        );

        $builder->editColumn(
            'total',
            function (Order $model) {
                return format_price($model->total);
            }
        );

        return $builder;
    }

    public function getColumns(): array
    {
        return [
            Column::checkbox(),
            Column::id(),
            Column::actions(),
            Column::editLink('code', admin_url('orders/{id}/edit'), __('Order Code')),
            Column::make('quantity'),
            Column::make('total'),
            Column::make('payment_method_name')->name(__('Payment Method')),
            Column::make('payment_status'),
            Column::make('delivery_status'),
            Column::createdAt()
        ];
    }

    public function actions(Model $model): array
    {
        return [
            Action::edit(admin_url("orders/{$model->id}/edit"))->can('orders.edit'),
            // Action::delete()->can('orders.delete'),
        ];
    }

    public function bulkActions(): array
    {
        return [
            // BulkAction::delete()->can('orders.delete'),
        ];
    }
}
