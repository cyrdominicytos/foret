<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'prenom' => $this->firstname,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'role' => \App\Models\User::find($this->id)->roles->first(),//->name,
            'links' => [
//                'self' => route('users.show', ['user' => $this->uuid]),
            ],
        ];
    }

}
