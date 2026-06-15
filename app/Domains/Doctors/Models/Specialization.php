<?php

namespace App\Domains\Doctors\Models;

use App\Domains\Images\Models\Image;
use App\Domains\Shared\Traits\ClearsCache;
use App\Enums\SpecializationEnum;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Specialization extends Model
{
    use HasUuidV7, ClearsCache;

    public function cacheVersionsToIncrement(): array
    {
        return ['specializations:cache_version'];
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public static function seedFromEnum(): void
    {
        $names = [
            'general_practitioner' => ['ar' => 'طبيب عام', 'en' => 'General Practitioner'],
            'internal_medicine' => ['ar' => 'طب داخلي', 'en' => 'Internal Medicine'],
            'cardiology' => ['ar' => 'طب القلب', 'en' => 'Cardiology'],
            'dermatology' => ['ar' => 'جلدية', 'en' => 'Dermatology'],
            'pediatrics' => ['ar' => 'طب الأطفال', 'en' => 'Pediatrics'],
            'orthopedics' => ['ar' => 'جراحة العظام', 'en' => 'Orthopedics'],
            'neurology' => ['ar' => 'طب الأعصاب', 'en' => 'Neurology'],
            'ophthalmology' => ['ar' => 'طب العيون', 'en' => 'Ophthalmology'],
            'ent' => ['ar' => 'أنف وأذن وحنجرة', 'en' => 'Ear, Nose & Throat (ENT)'],
            'psychiatry' => ['ar' => 'طب نفسي', 'en' => 'Psychiatry'],
            'radiology' => ['ar' => 'أشعة', 'en' => 'Radiology'],
            'anesthesiology' => ['ar' => 'تخدير', 'en' => 'Anesthesiology'],
            'emergency_medicine' => ['ar' => 'طب الطوارئ', 'en' => 'Emergency Medicine'],
            'obstetrics_gynecology' => ['ar' => 'نساء وتوليد', 'en' => 'Obstetrics & Gynecology'],
            'oncology' => ['ar' => 'الأورام', 'en' => 'Oncology'],
            'urology' => ['ar' => 'مسالك بولية', 'en' => 'Urology'],
            'gastroenterology' => ['ar' => 'جهاز هضمي', 'en' => 'Gastroenterology'],
            'pulmonology' => ['ar' => 'صدرية', 'en' => 'Pulmonology'],
            'rheumatology' => ['ar' => 'روماتيزم', 'en' => 'Rheumatology'],
            'endocrinology' => ['ar' => 'غدد صماء', 'en' => 'Endocrinology'],
            'nephrology' => ['ar' => 'أمراض الكلى', 'en' => 'Nephrology'],
            'hematology' => ['ar' => 'أمراض الدم', 'en' => 'Hematology'],
            'infectious_disease' => ['ar' => 'أمراض معدية', 'en' => 'Infectious Disease'],
            'sports_medicine' => ['ar' => 'طب رياضي', 'en' => 'Sports Medicine'],
            'plastic_surgery' => ['ar' => 'جراحة تجميل', 'en' => 'Plastic Surgery'],
            'general_surgery' => ['ar' => 'جراحة عامة', 'en' => 'General Surgery'],
            'neurosurgery' => ['ar' => 'جراحة أعصاب', 'en' => 'Neurosurgery'],
            'family_medicine' => ['ar' => 'طب أسرة', 'en' => 'Family Medicine'],
        ];

        $descriptions = [
            'general_practitioner' => ['ar' => 'طبيب عام يقدم الرعاية الصحية الأولية', 'en' => 'Primary healthcare physician'],
            'cardiology' => ['ar' => 'متخصص في أمراض القلب والأوعية الدموية', 'en' => 'Specialist in heart and cardiovascular diseases'],
        ];

        foreach (SpecializationEnum::cases() as $case) {
            $slug = $case->value;
            Specialization::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $names[$slug] ?? ['ar' => $slug, 'en' => $slug],
                    'description' => $descriptions[$slug] ?? null,
                ]
            );
        }
    }
}
