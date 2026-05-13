<?php

namespace Database\Seeders;

use App\Domains\Locations\Models\City;
use App\Domains\Locations\Models\Country;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
                'code' => 'SY',
                'flag' => 'https://flagcdn.com/sy.svg',
                'cities' => [
                    ['ar' => 'دمشق', 'en' => 'Damascus'],
                    ['ar' => 'حلب', 'en' => 'Aleppo'],
                    ['ar' => 'حمص', 'en' => 'Homs'],
                    ['ar' => 'اللاذقية', 'en' => 'Latakia'],
                    ['ar' => 'حماة', 'en' => 'Hama'],
                ],
            ],
            [
                'name' => ['ar' => 'مصر', 'en' => 'Egypt'],
                'code' => 'EG',
                'flag' => 'https://flagcdn.com/eg.svg',
                'cities' => [
                    ['ar' => 'القاهرة', 'en' => 'Cairo'],
                    ['ar' => 'الإسكندرية', 'en' => 'Alexandria'],
                    ['ar' => 'الجيزة', 'en' => 'Giza'],
                    ['ar' => 'الأقصر', 'en' => 'Luxor'],
                    ['ar' => 'أسوان', 'en' => 'Aswan'],
                ],
            ],
            [
                'name' => ['ar' => 'السعودية', 'en' => 'Saudi Arabia'],
                'code' => 'SA',
                'flag' => 'https://flagcdn.com/sa.svg',
                'cities' => [
                    ['ar' => 'الرياض', 'en' => 'Riyadh'],
                    ['ar' => 'جدة', 'en' => 'Jeddah'],
                    ['ar' => 'مكة المكرمة', 'en' => 'Mecca'],
                    ['ar' => 'المدينة المنورة', 'en' => 'Medina'],
                    ['ar' => 'الدمام', 'en' => 'Dammam'],
                ],
            ],
            [
                'name' => ['ar' => 'الإمارات', 'en' => 'United Arab Emirates'],
                'code' => 'AE',
                'flag' => 'https://flagcdn.com/ae.svg',
                'cities' => [
                    ['ar' => 'أبو ظبي', 'en' => 'Abu Dhabi'],
                    ['ar' => 'دبي', 'en' => 'Dubai'],
                    ['ar' => 'الشارقة', 'en' => 'Sharjah'],
                    ['ar' => 'عجمان', 'en' => 'Ajman'],
                    ['ar' => 'رأس الخيمة', 'en' => 'Ras Al Khaimah'],
                ],
            ],
            [
                'name' => ['ar' => 'العراق', 'en' => 'Iraq'],
                'code' => 'IQ',
                'flag' => 'https://flagcdn.com/iq.svg',
                'cities' => [
                    ['ar' => 'بغداد', 'en' => 'Baghdad'],
                    ['ar' => 'البصرة', 'en' => 'Basra'],
                    ['ar' => 'الموصل', 'en' => 'Mosul'],
                    ['ar' => 'أربيل', 'en' => 'Erbil'],
                    ['ar' => 'النجف', 'en' => 'Najaf'],
                ],
            ],
            [
                'name' => ['ar' => 'الأردن', 'en' => 'Jordan'],
                'code' => 'JO',
                'flag' => 'https://flagcdn.com/jo.svg',
                'cities' => [
                    ['ar' => 'عمان', 'en' => 'Amman'],
                    ['ar' => 'إربد', 'en' => 'Irbid'],
                    ['ar' => 'الزرقاء', 'en' => 'Zarqa'],
                    ['ar' => 'العقبة', 'en' => 'Aqaba'],
                    ['ar' => 'السلط', 'en' => 'Salt'],
                ],
            ],
            [
                'name' => ['ar' => 'فلسطين', 'en' => 'Palestine'],
                'code' => 'PS',
                'flag' => 'https://flagcdn.com/ps.svg',
                'cities' => [
                    ['ar' => 'القدس', 'en' => 'Jerusalem'],
                    ['ar' => 'رام الله', 'en' => 'Ramallah'],
                    ['ar' => 'نابلس', 'en' => 'Nablus'],
                    ['ar' => 'غزة', 'en' => 'Gaza'],
                    ['ar' => 'الخليل', 'en' => 'Hebron'],
                ],
            ],
            [
                'name' => ['ar' => 'لبنان', 'en' => 'Lebanon'],
                'code' => 'LB',
                'flag' => 'https://flagcdn.com/lb.svg',
                'cities' => [
                    ['ar' => 'بيروت', 'en' => 'Beirut'],
                    ['ar' => 'طرابلس', 'en' => 'Tripoli'],
                    ['ar' => 'صيدا', 'en' => 'Sidon'],
                    ['ar' => 'صور', 'en' => 'Tyre'],
                    ['ar' => 'بعلبك', 'en' => 'Baalbek'],
                ],
            ],
            [
                'name' => ['ar' => 'ليبيا', 'en' => 'Libya'],
                'code' => 'LY',
                'flag' => 'https://flagcdn.com/ly.svg',
                'cities' => [
                    ['ar' => 'طرابلس', 'en' => 'Tripoli'],
                    ['ar' => 'بنغازي', 'en' => 'Benghazi'],
                    ['ar' => 'مصراتة', 'en' => 'Misrata'],
                    ['ar' => 'سبها', 'en' => 'Sabha'],
                    ['ar' => 'طبرق', 'en' => 'Tobruk'],
                ],
            ],
            [
                'name' => ['ar' => 'تونس', 'en' => 'Tunisia'],
                'code' => 'TN',
                'flag' => 'https://flagcdn.com/tn.svg',
                'cities' => [
                    ['ar' => 'تونس', 'en' => 'Tunis'],
                    ['ar' => 'صفاقس', 'en' => 'Sfax'],
                    ['ar' => 'سوسة', 'en' => 'Sousse'],
                    ['ar' => 'القيروان', 'en' => 'Kairouan'],
                    ['ar' => 'بنزرت', 'en' => 'Bizerte'],
                ],
            ],
            [
                'name' => ['ar' => 'الجزائر', 'en' => 'Algeria'],
                'code' => 'DZ',
                'flag' => 'https://flagcdn.com/dz.svg',
                'cities' => [
                    ['ar' => 'الجزائر', 'en' => 'Algiers'],
                    ['ar' => 'وهران', 'en' => 'Oran'],
                    ['ar' => 'قسنطينة', 'en' => 'Constantine'],
                    ['ar' => 'عنابة', 'en' => 'Annaba'],
                    ['ar' => 'باتنة', 'en' => 'Batna'],
                ],
            ],
            [
                'name' => ['ar' => 'قطر', 'en' => 'Qatar'],
                'code' => 'QA',
                'flag' => 'https://flagcdn.com/qa.svg',
                'cities' => [
                    ['ar' => 'الدوحة', 'en' => 'Doha'],
                    ['ar' => 'الريان', 'en' => 'Al Rayyan'],
                    ['ar' => 'الوكرة', 'en' => 'Al Wakrah'],
                    ['ar' => 'الخور', 'en' => 'Al Khor'],
                    ['ar' => 'مسيعيد', 'en' => 'Mesaieed'],
                ],
            ],
            [
                'name' => ['ar' => 'الكويت', 'en' => 'Kuwait'],
                'code' => 'KW',
                'flag' => 'https://flagcdn.com/kw.svg',
                'cities' => [
                    ['ar' => 'الكويت', 'en' => 'Kuwait City'],
                    ['ar' => 'حولي', 'en' => 'Hawalli'],
                    ['ar' => 'الفروانية', 'en' => 'Farwaniya'],
                    ['ar' => 'الجهراء', 'en' => 'Jahra'],
                    ['ar' => 'الأحمدي', 'en' => 'Ahmadi'],
                ],
            ],
            [
                'name' => ['ar' => 'عمان', 'en' => 'Oman'],
                'code' => 'OM',
                'flag' => 'https://flagcdn.com/om.svg',
                'cities' => [
                    ['ar' => 'مسقط', 'en' => 'Muscat'],
                    ['ar' => 'صلالة', 'en' => 'Salalah'],
                    ['ar' => 'صحار', 'en' => 'Sohar'],
                    ['ar' => 'نزوى', 'en' => 'Nizwa'],
                    ['ar' => 'البريمي', 'en' => 'Al Buraimi'],
                ],
            ],
            [
                'name' => ['ar' => 'البحرين', 'en' => 'Bahrain'],
                'code' => 'BH',
                'flag' => 'https://flagcdn.com/bh.svg',
                'cities' => [
                    ['ar' => 'المنامة', 'en' => 'Manama'],
                    ['ar' => 'المحرق', 'en' => 'Muharraq'],
                    ['ar' => 'الرفاع', 'en' => 'Riffa'],
                    ['ar' => 'عيسى', 'en' => 'Isa Town'],
                    ['ar' => 'سترة', 'en' => 'Sitra'],
                ],
            ],
            [
                'name' => ['ar' => 'اليمن', 'en' => 'Yemen'],
                'code' => 'YE',
                'flag' => 'https://flagcdn.com/ye.svg',
                'cities' => [
                    ['ar' => 'صنعاء', 'en' => 'Sanaa'],
                    ['ar' => 'عدن', 'en' => 'Aden'],
                    ['ar' => 'تعز', 'en' => 'Taiz'],
                    ['ar' => 'الحديدة', 'en' => 'Hodeidah'],
                    ['ar' => 'المكلا', 'en' => 'Mukalla'],
                ],
            ],
            [
                'name' => ['ar' => 'السودان', 'en' => 'Sudan'],
                'code' => 'SD',
                'flag' => 'https://flagcdn.com/sd.svg',
                'cities' => [
                    ['ar' => 'الخرطوم', 'en' => 'Khartoum'],
                    ['ar' => 'أم درمان', 'en' => 'Omdurman'],
                    ['ar' => 'بورسودان', 'en' => 'Port Sudan'],
                    ['ar' => 'كسلا', 'en' => 'Kassala'],
                    ['ar' => 'الأبيض', 'en' => 'El Obeid'],
                ],
            ],
            [
                'name' => ['ar' => 'المغرب', 'en' => 'Morocco'],
                'code' => 'MA',
                'flag' => 'https://flagcdn.com/ma.svg',
                'cities' => [
                    ['ar' => 'الرباط', 'en' => 'Rabat'],
                    ['ar' => 'الدار البيضاء', 'en' => 'Casablanca'],
                    ['ar' => 'مراكش', 'en' => 'Marrakesh'],
                    ['ar' => 'فاس', 'en' => 'Fes'],
                    ['ar' => 'طنجة', 'en' => 'Tangier'],
                ],
            ],
        ];

        foreach ($countries as $countryData) {
            $cities = $countryData['cities'];
            unset($countryData['cities']);

            $country = Country::create($countryData);

            foreach ($cities as $city) {
                City::create([
                    'name' => $city,
                    'country_id' => $country->id,
                ]);
            }
        }
    }
}
