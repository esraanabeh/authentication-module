<?php

namespace Modules\Authentication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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

            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email ,
            'phone_number' => $this->phone_number ,
            'role' => $this->role ,
            'avatar' => $this->getFirstMediaUrl('avatar') 


        ];
    }
}
