<?php
/**
 * JUZAWEB CMS - Laravel CMS for Your Project
 *
 * @package    juzaweb/cms
 * @author     The Anh Dang
 * @link       https://cms.juzaweb.com
 * @license    GNU V2
 */

namespace Juzaweb\Modules\Payment\Http\DataTables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Juzaweb\Core\DataTables\Action;
use Juzaweb\Core\DataTables\BulkAction;
use Juzaweb\Core\DataTables\Column;
use Juzaweb\Core\DataTables\DataTable;
use Juzaweb\Modules\Payment\Models\PaymentMethod;
use Yajra\DataTables\EloquentDataTable;

class MethodsDataTable extends DataTable
{
    protected string $actionUrl = 'payment-methods/bulk';

    public function query(PaymentMethod $model): Builder
    {
        return $model->newQuery()->filter(request()->all());
    }

    public function getColumns(): array
    {
        return [
            Column::checkbox(),
            Column::id(),
            Column::editLink('name', admin_url('payment-methods/{id}/edit'), __('Name')),
            Column::make('sandbox', __('Sandbox'))->center()->width('100px'),
            Column::make('active', __('Active'))
                ->center()
                ->width('100px'),
            Column::createdAt(),
            Column::actions(),
        ];
    }

    public function renderColumns(EloquentDataTable $builder): EloquentDataTable
    {
        return $builder
            ->editColumn(
                'active',
                fn (PaymentMethod $model) => $model->active ? __('Yes') : __('No'))
            ->editColumn(
                'sandbox',
                fn (PaymentMethod $model) => $model->sandbox ? __('Yes') : __('No')
            );
    }

    public function bulkActions(): array
    {
        return [
            BulkAction::delete()->can('payment-methods.delete'),
        ];
    }

    public function actions(Model $model): array
    {
        return [
            Action::edit(admin_url("payment-methods/{$model->id}/edit"))
                ->can('payment-methods.edit'),
            Action::delete()
                ->can('payment-methods.delete'),
        ];
    }
}
