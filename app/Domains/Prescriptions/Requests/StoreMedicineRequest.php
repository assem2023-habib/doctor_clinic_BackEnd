<?php

namespace App\Domains\Prescriptions\Requests;

use App\Domains\Prescriptions\Models\Medicine;
use Illuminate\Foundation\Http\FormRequest;

class StoreMedicineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('medicines.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255', 'unique:medicines,name->en'],
            'description_ar' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $user = $this->user();
        if (!$user || !$user->hasRole('patient')) {
            return;
        }

        $validator->after(function ($validator) use ($user) {
            $todayCount = Medicine::where('created_by', 'like', $user->id . ' |%')
                ->whereDate('created_at', today())
                ->count();

            if ($todayCount >= 15) {
                $validator->errors()->add('daily_limit', __('You have reached the maximum of 15 medicine creations per day.'));
            }
        });
    }
}
