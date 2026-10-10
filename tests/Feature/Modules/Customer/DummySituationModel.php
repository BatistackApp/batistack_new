<?php

namespace Tests\Feature\Modules\Customer;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DummySituationModel extends Model
{
    protected $table = 'dummy_situation_table';

    protected $guarded = [];

    public function order(): BelongsTo
    {
        return $this->belongsTo(DummyOrderModel::class, 'customer_order_id');
    }
}
