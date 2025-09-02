<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * 
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string|null $email
 * @property bool $isOnline
 * @property bool $isMobileVerified
 * @property string|null $otp
 * @property string $role
 * @property float|null $longitude
 * @property float|null $latitude
 * @property string $phone_number
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Trip|null $trip
 *
 * @package App\Models
 */
class User extends Model
{
	protected $table = 'users';

	protected $casts = [
		'isOnline' => 'bool',
		'isMobileVerified' => 'bool',
		'longitude' => 'float',
		'latitude' => 'float',
		'email_verified_at' => 'datetime'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'name',
		'username',
		'email',
		'isOnline',
		'isMobileVerified',
		'otp',
		'role',
		'longitude',
		'latitude',
		'phone_number',
		'email_verified_at',
		'password',
		'remember_token'
	];

	public function trip()
	{
		return $this->hasOne(Trip::class, 'passenger_id');
	}
}
