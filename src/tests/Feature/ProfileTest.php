<?php

namespace Tests\Feature;

use App\Models\People;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class ProfileTest extends TestCase
{
  use RefreshDatabase;
  use WithoutMiddleware;

  private $people;

  public function setUp(): void
  {
    parent::setUp();

    // Provide mocking data for testing
    $this->people = People::factory()->count(2)->create();
  }

  /**
   * A basic feature test example.
   *
   * @return void
   */
  public function testQueriesPeople(): void
  {
    // 1. Create Mock
    $people = $this->people;
    // 2. GraphQL Query
    $response = $this->graphQL(/** @lang GraphQL */ '
      query ($first: Int!){
        people(first: $first){
          edges{
            node{
              PeopleUsername
            }
          }
        }
      }', [
        'first' => 2
      ]
    );
    // 3. Verify and Assertion
    $response->assertJson([
      'data' => [
        'people' => [
          'edges' => [
            0 => [
              'node' => [
                'PeopleUsername' => $people[0]->PeopleUsername,
              ],
            ],
            1 => [
              'node' => [
                'PeopleUsername' => $people[1]->PeopleUsername,
              ],
            ],
          ],
        ],
      ],
    ]);
  }
}
