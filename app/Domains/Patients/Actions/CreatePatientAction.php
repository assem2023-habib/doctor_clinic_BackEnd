<?php

namespace App\Domains\Patients\Actions;

use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Domains\Patients\Requests\StorePatientRequest;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class CreatePatientAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(StorePatientRequest $request): User
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

        $user->assignRole('patient');

        $user->patient()->create([]);

        if ($request->hasFile('file')) {
            $this->uploadImageAction->execute(UploadImageData::fromArray([
                'file' => $request->file('file'),
                'type' => ImageTypeEnum::User,
                'imageable_id' => $user->id,
            ]));
        }

        $user->load('patient', 'roles');

        return $user;
    }
}
