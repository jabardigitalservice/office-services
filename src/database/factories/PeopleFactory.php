<?php

namespace Database\Factories;

use App\Models\People;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeopleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = People::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        static $number = 1;

        return [
            'PeopleKey' => 'XxJyPn38Yh',
            'PeopleId' => $number++,
            'PeopleName' => $this->faker->name(),
            'PeoplePosition' => 'POSITION' . $number++,
            'PeopleUsername' => $this->faker->userName(),
            'PeoplePassword' => 'ab190d3ca3df25c1c7c9d75ac724ff341f25eb10',
            'PeopleActiveStartDate' => '2020-03-22',
            'PeopleActiveEndDate' => '2050-04-16',
            'PeopleIsActive' => 1,
            'PrimaryRoleId' => 'uk.1.31.1.2.2',
            'GroupId' => 3,
            'RoleAtasan' => 'uk.1.31.1.2',
            'NIP' => '198402132015032003' . $number++,
            'ApprovelName' => $this->faker->name(),
            'Email' => $this->faker->email(),
            'NIK' => '327322530284000' . $number++,
            'Pangkat' => 'Penata Muda TK.I',
            'Eselon' => 'IV.a',
            'Golongan' => 'III.b'
        ];
    }
}
