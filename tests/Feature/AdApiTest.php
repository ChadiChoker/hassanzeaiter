<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_ad_with_valid_data(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'external_id' => '23',
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $field = CategoryField::create([
            'category_id' => $category->id,
            'external_id' => 'field_1',
            'field_key' => 'test_field',
            'field_label' => 'Test Field',
            'field_type' => 'text',
            'is_required' => true,
        ]);

        $adData = [
            'category_id' => $category->id,
            'title' => 'Test Ad Title',
            'description' => 'This is a test ad description',
            'price' => 100.50,
            'fields' => [
                'test_field' => 'Test value',
            ],
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ads', $adData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'price',
                    'status',
                    'category',
                    'user',
                    'dynamic_fields',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Ad Title',
                    'description' => 'This is a test ad description',
                    'price' => '100.50',
                ],
            ]);

        $this->assertDatabaseHas('ads', [
            'title' => 'Test Ad Title',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertDatabaseHas('ad_field_values', [
            'value' => 'Test value',
            'category_field_id' => $field->id,
        ]);
    }

    public function test_validation_fails_when_required_fields_missing(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'external_id' => '23',
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        CategoryField::create([
            'category_id' => $category->id,
            'external_id' => 'field_1',
            'field_key' => 'required_field',
            'field_label' => 'Required Field',
            'field_type' => 'text',
            'is_required' => true,
        ]);

        $adData = [
            'category_id' => $category->id,
            'title' => 'Test Ad',
            'description' => 'Test description',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ads', $adData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fields.required_field']);

 
        $this->assertDatabaseCount('ads', 0);
    }

    public function test_validation_fails_when_title_is_missing(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'external_id' => '23',
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $adData = [
            'category_id' => $category->id,
            'description' => 'Test description',
        ];

        $response = $this->actingAs($user)
            ->postJson('/api/v1/ads', $adData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_create_ad(): void
    {
        $category = Category::create([
            'external_id' => '23',
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $adData = [
            'category_id' => $category->id,
            'title' => 'Test Ad',
            'description' => 'Test description',
        ];

        $response = $this->postJson('/api/v1/ads', $adData);

        $response->assertStatus(401);
    }
}
