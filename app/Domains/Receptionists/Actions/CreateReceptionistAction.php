<?php

namespace App\Domains\Receptionists\Actions;

use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Domains\Receptionists\Requests\StoreReceptionistRequest;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class CreateReceptionistAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(StoreReceptionistRequest $request): User
    {
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'gender' => $request->gender,
            'birthday_date' => $request->birthday_date,
            'is_active' => true,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole('receptionist');

        $user->receptionist()->create([
            'shift_start' => $request->shift_start,
            'shift_end' => $request->shift_end,
        ]);

        if ($request->hasFile('file')) {
            $this->uploadImageAction->execute(UploadImageData::fromArray([
                'file' => $request->file('file'),
                'type' => ImageTypeEnum::User,
                'imageable_id' => $user->id,
            ]));
        }

        $user->load('receptionist', 'roles');

        return $user;
    }
}
