<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Trip
 * 
 * @property string $pickup_address
 * @property string $destination_address
 * @property string $price
 * @property string $status
 * @property string $transport_type
 * @property int $passenger_id
 * @property int|null $driver_id
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class Trip extends Model
{
	protected $table = 'trips';
	public $incrementing = false;

	protected $casts = [
		'passenger_id' => 'int',
		'driver_id' => 'int',
		'started_at' => 'datetime',
		'completed_at' => 'datetime',
		'cancelled_at' => 'datetime'
	];

	protected $fillable = [
		'pickup_address',
		'destination_address',
		'price',
		'status',
		'transport_type',
		'passenger_id',
		'driver_id',
		'started_at',
		'completed_at',
		'cancelled_at',
		'cancellation_reason'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'passenger_id');
	}
}
