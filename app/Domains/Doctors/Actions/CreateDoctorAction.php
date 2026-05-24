<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\Requests\StoreDoctorRequest;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Enums\ImageTypeEnum;
use App\Models\User;

class CreateDoctorAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(StoreDoctorRequest $request): User
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

        $user->assignRole('doctor');

        $user->doctor()->create([
            'specialization_id' => $request->specialization_id,
            'experience_months' => (int) $request->experience_months,
        ]);

        if ($request->hasFile('file')) {
            $this->uploadImageAction->execute(UploadImageData::fromArray([
                'file' => $request->file('file'),
                'type' => ImageTypeEnum::User,
                'imageable_id' => $user->id,
            ]));
        }

        $user->load('doctor.schedules', 'roles');

        return $user;
    }
}
