<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'email'        => $this->email,
            'avatar'       => $this->getFirstMediaUrl('avatar') ,
            'is_verified'  => $this->is_verified,
            'status'       => $this->status,
            'allow_2fa'    => $this->allow_2fa,
            'role'         => $this->role ,
            'phone_number' => $this->phone_number,
            'role'         => $this->role
        ];
    }
}
